<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2020/7/21
 * Time: 14:21
 */
namespace app\api\controller;

use app\common\service\SwooleService;
use app\common\service\ToolService;
use think\cache\driver\Redis;
use think\swoole\Server;
use think\swoole\Timer;
use Swoole\Timer as SwooleTimer;
/**
 * swoole 服务器
 * Class Server
 * @package app\http\controller
 */
class Swoole extends Server
{
	protected $host = '0.0.0.0';
	protected $serverType = 'socket';
	protected $port = 9521;
	protected $option = [
		'worker_num' => 4,
		'daemonize' => true,
		'backlog' => 128
	];

	/**
	 * 监听链接
	 * @param $server
	 * @param $request
	 */
	public function onOpen($server, $request)
	{
		echo "server: handshake success with fd{$request->fd}\n";
	}

	/**
	 * 监听数据接收
	 * @param $server
	 * @param $frame
	 * @throws \think\Exception
	 * @throws \think\db\exception\DataNotFoundException
	 * @throws \think\db\exception\ModelNotFoundException
	 * @throws \think\exception\DbException
	 * @throws \think\exception\PDOException
	 */
	public function onMessage($server, $frame)
	{
//        echo "receive from {$frame->fd}:{$frame->data},opcode:{$frame->opcode},fin:{$frame->finish}\n";
		$data = json_decode($frame->data, true);

        p($data,false,env('runtime_path') . date('Ymd') . '-push.txt');

        $redis = new Redis();
        if(isset($data['admin_token'])){
            if(md5(json_encode($data['data']).'token') == $data['admin_token']){
                if (!(empty($data['data']))) {
                    foreach ($data['data'] as $v) {
                        if (isset($v['message']['user_id'])) {
                            $fd = $redis->get($v['message']['user_id']);
                            if ($this->swoole->isEstablished($fd)) {
                                $push = [
                                    'code' => 10000,
                                    'msg' => '推送成功！',
                                    'data' => $v,
                                ];
                                $server->push($fd, json_encode($push));
                                echo '推送成功！' . "\n";
                            }
                        }
                    }
                }
            }
            return false;
        }
		$data['fd'] = $frame->fd;
		$result = SwooleService::initial($data, $frame->fd);
		if ($result['code'] != 10000) {
			$server->push($frame->fd, json_encode($result));
//			$server->disconnect($frame->fd,$result['code'],$result['msg']);
		}

		if (isset($result['data']['state']) && $result['data']['state'] == 0) {
			$server->push($frame->fd, json_encode($result));
		} else {
			$redis = new Redis();
			if (isset($result['data']['room'])) {
				foreach ($result['data']['room'] as $v) {
					$fd = $redis->get($v);
					if ($this->swoole->isEstablished($fd)) {
						$server->push($fd, json_encode($result));
					}
				}
			}
		}
	}

	/**
	 * 监听连接关闭
	 * @param $server
	 * @param $fd
	 */
	public function onClose($server, $fd)
	{
		$redis = new Redis();
		$token = $redis->get('order_' . $fd);
		if ($token) {
			$result = SwooleService::leave($token);
			if ($result['code'] == 10000) {
				$redis->rm('order_' . $fd);
				echo '客户端' . $fd . '关闭成功--' . PHP_EOL;
			} else {
				echo '客户端' . $fd . '关闭失败--' . PHP_EOL . '，失败原因：' . $result['msg'];
			}
		} else {
			echo '客户端' . $fd . '非用户关闭--' . PHP_EOL;
		}
	}

}