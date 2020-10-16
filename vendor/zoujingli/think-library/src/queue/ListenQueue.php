<?php

// +----------------------------------------------------------------------
// | ThinkAdmin
// +----------------------------------------------------------------------
// | 重庆乐湛科技有限公司
// +----------------------------------------------------------------------
// | 官方网站: http://demo.thinkadmin.top
// +----------------------------------------------------------------------
// | 开源协议 ( https://mit-license.org )
// +----------------------------------------------------------------------
// | gitee 代码仓库：https://gitee.com/zoujingli/ThinkAdmin
// | github 代码仓库：https://github.com/zoujingli/ThinkAdmin
// +----------------------------------------------------------------------

namespace library\queue;

use library\service\ProcessService;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\Db;

/**
 * 启动监听任务的主进程
 * Class ListenQueue
 * @package library\queue
 */
class ListenQueue extends Command
{
    /**
     * 当前进程服务
     * @var ProcessService
     */
    protected $process;

    /**
     * 配置指定信息
     */
    protected function configure()
    {
        $this->setName('xtask:listen')->setDescription('Start task listening main process');
    }

    /**
     * 初始化进程服务
     * @param Input $input
     * @param Output $output
     */
    protected function initialize(Input $input, Output $output)
    {
        $this->process = ProcessService::instance();
    }

    /**
     * 执行进程守护监听
     * @param Input $input
     * @param Output $output
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    protected function execute(Input $input, Output $output)
    {
        set_time_limit(0);
        Db::name('SystemQueue')->count();
        if ($this->process->iswin() && function_exists('cli_set_process_title')) {
            cli_set_process_title("ThinkAdmin {$this->process->version()} Queue Listen");
        }
        $output->writeln('============ LISTENING ============');
        while (true) {
            $map = [['status', 'eq', '1'], ['time', '<=', time()]];
            foreach (Db::name('SystemQueue')->where($map)->order('time asc')->select() as $vo) {
                try {
                    $command = $this->process->think("xtask:_work {$vo['id']} -");
                    if (count($this->process->query($command)) > 0) {
                        $this->output->writeln("Already in progress -> [{$vo['id']}] {$vo['title']}");
                    } else {
                        $this->process->create($command);
                        $this->output->writeln("Created new process -> [{$vo['id']}] {$vo['title']}");
                    }
                } catch (\Exception $exception) {
                    Db::name('SystemQueue')->where(['id' => $vo['id']])->update(['status' => '4', 'desc' => $exception->getMessage()]);
                    $output->error("Execution failed -> [{$vo['id']}] {$vo['title']}，{$exception->getMessage()}");
                }
            }
            sleep(1);
        }
    }

}
