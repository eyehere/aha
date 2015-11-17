<?php
/*
  +----------------------------------------------------------------------+
  | Aha                                                                  |
  +----------------------------------------------------------------------+
  | This source file is subject to version 2.0 of the Apache license,    |
  | that is bundled with this package in the file LICENSE, and is        |
  | available through the world-wide-web at the following url:           |
  | http://www.apache.org/licenses/LICENSE-2.0.html                      |
  | If you did not receive a copy of the Apache2.0 license and are unable|
  | to obtain it through the world-wide-web, please send a note to       |
  | yiming_6weijun@163.com so we can mail you a copy immediately.        |
  +----------------------------------------------------------------------+
  | Author: Weijun Lu  <yiming_6weijun@163.com>                          |
  +----------------------------------------------------------------------+
*/
return array(
	'mode'			=> 'coroutine',//开启协程方式运行框架
	//进程配置
	'process'	=>	array(
		'drive_worker_num'		=>	1,//驱动进程
		'task_worker_num'		=>	20,//工作进程
		'stats_worker_num'		=>	1,//状态监测进程
		'redo_worker_num'		=>	1//错误重试进程
	),
	//驱动进程参数配置
	'drive'	=> array(
		'interval'      =>	20000,		//单位 ms 20s
		'max_task'      =>	100,	//单个进程同时在处理的任务的数量上限
		'max_process_num'=>	2000,	//所有进程同时处理的最大任务数量
	),
    //redo进程参数配置
	'redo'	=> array(
		'interval'          =>	600000,	//单位 ms 10分钟
        'trigger_interval'  =>  10000,  //单位ms  10s一触发
	),
    //stats进程参数配置
	'stats'	=> array(
		'interval'      =>	300000,	//单位 ms 5分钟
        'stats_interval'=>  10000,//每10S进行状态监测
	),
    'city_dir'   =>  dirname(__DIR__) . '/Data/',
);