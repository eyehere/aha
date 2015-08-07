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
namespace Aha\Network;

abstract class Server {
	
	//server启动的配置选项信息
	protected $_config = array();
	
	//创建的server的实例
	//属性列表 $setting $master_pid $manager_pid $worker_id 
	//		  $worker_pid $taskworker $connections
	protected $_objServer	= null;
	
	//项目名称
	protected $_appName	= 'Aha';

	//================Server start BEGIN==============================================
	/**
	 * @brief 初始化server
	 * @param \swoole_server $server 在外部创建 本类只完成创建的繁琐的工作 
	 *			不会去做那种过度封装的工作 最大的自由留给开发者
	 * @return \Aha\Server
	 */
	public function __construct(\swoole_server $server, \string $appName='', array $arrSetting = array() ) {
		$this->_objServer = $server;
		if ( !empty($appName) ) {
			$this->_appName = $appName;
		}
		if ( !isset($this->_objServer->setting) ) {
			$this->_objServer->set( $this->getConfig($arrSetting) );
		}
		$this->_initEvents();
		return $this;
	}
	
	//初始化事件回调
	protected function _initEvents() {
		//主线程
		$this->_objServer->on('start', array($this,'onStart') );
		//manager进程
		$this->_objServer->on('managerStart', array($this, 'onManagerStart'));
		$this->_objServer->on('managerStop', array($this,'onManagerStop') );
		//worker进程
		$this->_objServer->on('pipeMessage', array($this, 'onPipeMessage') );
		$this->_objServer->on('workerStart', array($this, 'onWorkerStart') );
		$this->_objServer->on('workerStop', array($this, 'onWorkerStop'));
		$this->_objServer->on('workerError', array($this, 'onWorkerError') );
		//task进程回调worker进程
		$this->_objServer->on('finish', array($this, 'onFinish') );
		//task进程
		$this->_objServer->on('task', array($this, 'onTask') );
		//定时器
		$this->_objServer->on('timer', array($this, 'onTimer') );
		//主线程
		$this->_objServer->on('shutdown', array($this, 'onShutdown') );
		return $this;
	}
	
	//获取server实例
	public function getServer() {
		return $this->_objServer;
	}
	
	//server状态监控
	public function stats() {
		return $this->_objServer->stats();
	}

	//================Server start END==============================================
	
	//================配置项BEGIN==============================================
	/**
	 * @brief 获取server的配置选项
	 */
	public function getConfig( array $arrSetting = array() ) {
		if ( empty($this->_config) ) {
			$this->setConfig($arrSetting);
		}
		return $this->_config;
	}
	
	/**
	 * @brief 设置server的配置选项
	 */
	public function setConfig(array $config) {
		$default = array(
			'daemonize'			=>	1,//守护进程化
			'reactor_num'		=>	swoole_cpu_num(),//设置成cpu核数
			'worker_num'		=>	swoole_cpu_num(),//设置成cpu核数的两倍以最大程度利用CPU
			'max_request'		=>	100000,//worker进程的最大任务数
			'max_connection'	=>	20000,//最大允许的连接数
			'task_worker_num'	=>	swoole_cpu_num(),//设置成cpu核数
			'task_ipc_mode'		=>	3,//(1:使用unixsocket通信 2:消息队列 3:消息队列且争抢)
			'task_max_request'	=>	2000,//task进程最大任务数
			//'task_tmp_dir'	=>	'',//task数据临时目录
			'dispatch_mode'		=>	2,//(1:轮询模式2:固定模式3:抢占模式4:IP分配5:UID分配)
			//'message_queue_key'=>	'',//(在ipc_mode=2或者task_ipc_mode=2时使用)
			'backlog'			=>	128,//Listen队列的长度
			'log_file'			=>	'./logs/Aha.log',//错误日志文件
			'heartbeat_check_interval'=>60,//心跳检测间隔时间
			'heartbeat_idle_time'=>	600,//连接最大允许空闲时间
			'open_eof_check'	 =>	true,//打开eof检测
			'package_eof'		 =>	"\r\n",//设置eof(最大允许8字节)
			//'open_eof_split'	 =>	1,//启用eof自动分包
			//'package_length_type'=>	'',//长度值的类型
			//'package_max_length' =>	'',//最大数据包长度
			'open_cpu_affinity'	 => 1,//CPU亲和性绑定
			//'cpu_affinity_ignore'=> array(),//忽略CPU亲和性绑定的CPU核ID
			'open_tcp_nodelay'	=> 1,//开启后TCP发送数据会关闭Nagle合并算法，立即发往客户端
			//'tcp_defer_accept'	=> 5,//TCP链接有数据发送才触发accept
			'user'				=> 'www',
			'group'				=> 'www',
			'chroot'			=> '/tmp/',
			//'pipe_buffer_size'	=>	'',//管道通信内存缓存区长度
			//'buffer_output_size'=> '',//数据发送缓存区
			'discard_timeout_request'=>true,//worker进程收到已关闭连接的数据请求，将自动丢弃
		);
		
		if ( empty($config) ) {
			$this->_config = $default;
			return $this;
		}
		
		foreach ( $config as $key=>$val ) {
			if ( isset($default[$key]) ) {
				$default[$key] = $val;
			}
		}
		
		$this->_config = $default;
		return $this;
	}
	
	//================配置项END================================================
	
	//================回调函数BEGIN==============================================
	/**
	 * @brief 在worker、task进程启动时发生
	 * $workerId大于$server->setting['worker_num']时：表示是task进程
	 * 在onWorkerStart之前载入公共的不易改变的代码：所有worker共享，不需要额外的内存
	 * 想用$server->reload()重载代码，必须在onWorkerStart中require业务文件
	 */
	public function onWorkerStart(\swoole_server $server, \int $workerId) {
		if ( $workerId > $this->_objServer->setting['worker_num'] ) {
			swoole_set_process_name($this->_appName .'-Task-'.$workerId);
		} else {
			swoole_set_process_name($this->_appName .'-Worker-'.$workerId);
		}
	}
	
	/**
	 * @brief 当worker投递的任务在task进程中回调 
	 * task进程中的onTask事件中没有调用finish方法或者return结果，worker进程不会出发onFinish
	 * @param \swoole_server $server
	 * @param int $taskId
	 * @param string $data
	 */
	public function onFinish(\swoole_server $server, \int $taskId, \string $data) {
		
	}
	
	/**
	 * @brief 在worker进程终止的时候发生，可以回收worker进程申请的各类资源
	 */
	public function onWorkerStop(\swoole_server $server, \int $workerId) {
		
	}
	
	/**
	 * @brief 在worker进程内被调用
	 * $taskId和$fromId组合起来才是全局唯一的，不同的worker进程投递的任务ID可能会相同
	 * 通过return触发worker进程的onFinish函数，表示投递task完成
	 */
	public function onTask(\swoole_server $server, \int $taskId, \int $fromId, \string $data) {
		
	}
	
	/**
	 * @brief 当工作进程收到sendMessage发送的管道消息时会触发onPipeMessage事件
	 * worker/task进程都可能触发
	 */
	public function onPipeMessage(\swoole_server $server, \int $fromWorkerId, \string $message) {
		
	}
	
	/**
	 * @brief Server启动在主进程的主线程回调此函数
	 * 在此回调之前已经创建了manager进程、worker进程、监听所有端口和定时器
	 * 接下来主Reactor开始接收事件
	 * 建议操作：echo 写日志 修改进程名称 记录master和manager的进程ID
	 */
	public function onStart(\swoole_server $server) {
		swoole_set_process_name($this->_appName .'-Master');
	}
	
	/**
	 * @brief 在Server结束时发生
	 * 在此之前已经关闭了所有线程、worker进程、close所有监听端口、关闭主Reactor
	 * 注意：kill -15发送SIGTREM信号到主进程才能按正常流程终止
	 */
	public function onShutdown(\swoole_server $server) {
		
	}
	
	/**
	 * @brief 定时器触发
	 * $interval的值用来区分是哪个定时器触发的
	 */
	public function onTimer(\swooler_server $server, \int $interval) {
		
	}
	
	/**
	 * @brief 当worker/task进程发生异常会在Manager进程内回调此函数
	 * 主要用于监控和报警 很有可能遇到了致命错误或者coredump
	 */
	public function onWorkerError(\swoole_server $server, \int $workerId, \int $workerPid, \int $exitCode) {
		
	}
	
	/**
	 * @brief 当管理进程启动时调用它
	 * 在这个时刻可以修改进程的名称
	 */
	public function onManagerStart(\swoole_server $server) {
		swoole_set_process_name( $this->_appName . '-Manager');
	}
	
	/**
	 * @brief 当管理进程结束时调用它
	 */
	public function onManagerStop(\swoole_server $server) {
		
	}
	
	//================回调函数  END==============================================
	
}
