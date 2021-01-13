<?php

namespace app\api\service;

use app\common\model\StoreChat;
use app\common\model\StoreChatMessage;
use app\common\service\ToolService;
use think\Db;
use think\Model;
use think\Validate;

class SwooleService extends Model
{
    /**
     * 获取聊天token
     * @param $uid 用户id
     * @param $type 类型【1.用户 2.客服】
     * @param $target 目标id
     * @return string
     */
    public function getToken($uid,$type,$target){
        //生成token
        $token = [
            'uid' => $uid, //用户 、客服id
            'type' => $type, //1.用户 2.客服
            'target' => $target //目标用户
        ];
        $token = serialize($token);
        $token = ToolService::opensslEncryption($token);
        return $token;
    }
    /**
     * 解析数据
     * @param $param 数据
     * @param $server
     * @param $fd  唯一用户id
     * @return array|bool
     */
    public function initial($param, $server, $fd)
    {
        try {
            if(!is_array($param)){
                $param = json_decode($param,1);
            }

            $validate = new Validate([
                'token' => 'require',
                'action' => 'require|in:chat,login',
            ]);
            if (!$validate->check($param)) throw exception($validate->getError());
            $token = ToolService::opensslDecrypt($param['token']);
            $token = unserialize($token);
            Db::startTrans();
            switch ($param['action']) {
                case 'chat':
                    $result = self::chat($token, $server, $param);
                    break;
                case 'login':
                    $result = self::login($token, $server, $fd);
                    break;
            }
        } catch (\Exception $exception) {
            $this->error = $exception->getMessage();
            Db::rollback();
            p($exception->getLine());
            return false;
        }
        Db::commit();
        return $result;
    }
    /**
     * 登录socket
     * @param mixed $token
     * @param mixed|null $server
     * @param string $param
     * @return \think\db\Query|void
     */
    public static function login($token, $server, $fd)
    {
        if ($token['type'] == 1) {//用户
            $server->addUser($fd, $token['uid']);
        } else {//客服
            $server->addCustomer($fd, $token['uid']);
        }
        $return = [
            'code' => 10000,
            'msg' => '登录成功',
            'data' => [
                'action' => 'login',
                'time' => time()
            ]
        ];
        return $return;
    }

    /**
     * 发送聊天内容
     * @param $token
     * @param $param
     * @return array
     */
    public static function chat($token, $server, $param)
    {
        $validate = new Validate([
            'content|聊天内容' => 'require',
        ]);
        if (!$validate->check($param)) throw exception($validate->getError());
        //会话是否存在
        if ($token['type'] == 1) {//用户
            $where['mid'] = $token['uid'];
            $where['sys_id'] = $token['target'];
            $target = $server->getCustomer($token['target']);
            $new = 'is_new_sys';
        } else {//客服
            $where['sys_id'] = $token['uid'];
            $where['mid'] = $token['target'];
            $target = $server->getUser($token['target']);
            $new = 'is_new_user';
        }
        $chat = Db::name('StoreChat')->where($where)->where('is_deleted', 0)->find();
        if (!$chat) {
            $chat['id'] = Db::name('StoreChat')->insertGetId($where);
            if (!$chat['id']) throw exception('发送失败');
        }
        //写入聊天记录
        $res = Db::name('StoreChatMessage')->insert([
            'chat_id' => $chat['id'],
            'mid' => $where['mid'],
            'sys_id' => $where['sys_id'],
            'send' => $token['type'],
            'content' => $param['content'],
        ]);
        if (!$res) throw exception('发送失败');
        //更改会话
        Db::name('StoreChat')->where('id',$chat['id'])->update([
            $new => 1,
            'update_at' =>time()
        ]);
        $return = [
            'code' => 10000,
            'msg' => '发送成功',
            'data' => [
                'action' => 'chat',
                'content' => $param['content'],
                'chat_id'=>$chat['id'],
                'fd' => $target,
                'time' => ToolService::time_tran(time(),1)
            ]
        ];
        return $return;
    }
}