<?php

namespace app\api\controller;

use think\Queue;

/**
 * 队列测试类   启动队列命令：php think queue:work --queue name --daemon
 * name 队列名称
 * daemon 是否循环
 * Class QueueTest
 * @package app\api\controller
 */
class QueueTest {
    public function index()
    {
        //队列处理  启动命令 php think queue:work --queue task_dis --daemon
        $jobHandlerClassName  = 'app\api\controller\Queue';
        // 2.当前任务归属的队列名称，如果为新队列，会自动创建
        $jobQueueName = "";

        // 3.当前任务所需的业务数据 . 不能为 resource 类型，其他类型最终将转化为json形式的字符串
        //   ( jobData 为对象时，存储其public属性的键值对 )
        $jobData = [];

        // 4.将该任务推送到消息队列，等待对应的消费者去执行
        $isPushed = Queue::later(1,$jobHandlerClassName , $jobData , $jobQueueName );
        if( $isPushed !== false ){
            return true;
        }else{
            return false;
        }
    }
}