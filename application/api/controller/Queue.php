<?php
/**
 * 这是一个消费者类，用于处理 order 队列中的任务
 */
namespace app\api\controller;

use app\api\controller\rider\service\RiderService;
use app\api\controller\store\service\MessageService;
use app\common\model\FoodOrder;
use app\common\model\FoodRunOrder;
use app\common\model\FoodRunRider;
use app\common\model\FoodRunTask;
use app\common\service\SmsService;
use think\Db;
use think\queue\Job;

class Queue {

    /**
     * fire方法是消息队列默认调用的方法
     * @param Job            $job      当前的任务对象
     * @param array|mixed    $data     发布任务时自定义的数据
     */
    public function fire(Job $job,$data)
    {
        p($data,false,env('runtime_path') . date('Ymd') . '-queue.txt');
        try {
            $isJobDone = $this->orderJob($data);
            if ($isJobDone['code'] == 10000) {
                // 如果任务执行成功， 记得删除任务
                $job->delete();
                p("",false,env('runtime_path') . date('Ymd') . '-queue-run-task.txt');
            } else {
                if ($job->attempts() > 3) {
                    //通过这个方法可以检查这个任务已经重试了几次了
//                    print("已经重复进行3次以上，任务删除!");
//                    p($data, false, env('runtime_path') . date('Ymd') . '-queue-run-task.txt');
//                    $job->delete();

                    // 也可以重新发布这个任务
                    $job->release(2); //$delay为延迟时间，表示该任务延迟2秒后再执行
                }
                p("",false,env('runtime_path') . date('Ymd') . '-queue-run-task.txt');
            }
        }catch (\Exception $exception)
        {
            p($exception->getMessage(),false,env('runtime_path') . date('Ymd') . '-queue-run-task.txt');
        }
    }

    /**
     * 订单任务处理
     * @param $data
     * @return array
     * @throws \AlibabaCloud\Client\Exception\ClientException
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    private function orderJob($data)
    {

    }

    /**
     * 有些消息在到达消费者时,可能已经不再需要执行了
     * @param array|mixed    $data     发布任务时自定义的数据
     * @return boolean                 任务执行的结果
     */
    private function checkDatabaseToSeeIfJobNeedToBeDone($data){
        return true;
    }
}