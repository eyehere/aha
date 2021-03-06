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
namespace Aha;

class Daemon {
	
	//当前application的名字
	private $_appNamespace = null;
	
	//部署环境
	private $_environ = 'product';
	
	//loader instance
	private $_loader = null;
	
	//config instance
	private $_objConfig	= null;
	
	//application instance
	private static $_instance = null;
	
	//coroutine scheduler
	private $_objScheduler = null;
	
	/**
	 * @brief daemon 单例 
	 * @param string $appNamespace
	 * @param string $environ
	 * @return type
	 */
	public static function getInstance( $appNamespace,  $environ = 'product') {
		if ( null === self::$_instance ) {
			self::$_instance = new \Aha\Daemon($appNamespace, $environ);
		}
		return self::$_instance;
	}
	
	/**
	 * @brief 应用引导程序初始化
	 * @param string $appNamespace
	 * @param string $environ
	 * @return \Aha\Daemon
	 */
	public function __construct( $appNamespace,  $environ = 'product') {
		$this->_appNamespace	= $appNamespace;
		$this->_environ			= $environ;
		$this->_initEnv();
		$this->_initLoader();
		return $this;
	}
	
	/**
	 * @brief 环境初始化相关
	 */
	protected function _initEnv() {
		define('AHA_DECLINED', -1);//交给下一个处理流程处理
		define('AHA_AGAIN', -2);//需要再次调度
	}
	
	/**
	 * @brief 初始化自动加载器
	 */
	protected function _initLoader() {
		$this->_loader = \Aha\Mvc\Loader::getInstance();
	}
	
	/**
	 * @brief 在server的第一行就加载Bootstrap文件，并调用此静态方法初始化Loader
	 * @return Loader 可以在Loader中继续注册更多的命名空间个路径的对应关系
	 */
	public static function initLoader() {
		define('AHA_PATH', dirname(__DIR__));
		define('AHA_EXT', '.php');
		require_once AHA_PATH . '/Aha/Mvc/Loader.php';
		
		$loader = \Aha\Mvc\Loader::getInstance();
		$loader->registerNamespace('Aha', AHA_PATH );

		spl_autoload_register( array($loader, 'autoload') );
		return $loader;
	}
	
	/**
	 * @brief 初始化配置项
	 */
	protected function _initConfig() {
		$this->_objConfig = new \Aha\Mvc\Config($this);
	}
	
	/**
	 * @brief 获取自动加载器实例
	 * @return type
	 */
	public function getLoader() {
		return $this->_loader;
	}
	
	/**
	 * @brief 获取配置实例
	 * @return type
	 */
	public function getConfig() {
		return $this->_objConfig;
	}
	
	/**
	 * @brief 获取部署环境
	 * @return type
	 */
	public function getEnviron() {
		return $this->_environ;
	}
	
	/**
	 * @brief 获取application namespace
	 * @return type
	 */
	public function getAppNamespace() {
		return $this->_appNamespace;
	}
	
	/**
	 * @brief 如果是运行在协程模式下 开启协程调度器
	 * @return type
	 */
	protected function _initScheduler() {
		$mode = $this->_objConfig->get('aha','mode');
		if ( empty($mode) || $mode !== 'coroutine' ) {
			$this->_objScheduler = null;
			return;
		}
		$this->_objScheduler = \Aha\Coroutine\Scheduler::getInstance();
	}
	
	/**
	 * @brief 获取协程调度器
	 * @return type
	 */
	public function getScheduler() {
		return $this->_objScheduler;
	}

	/**
	 * @brief application run main function
	 * @return \Aha\Bootstrap
	 * @throws \Exception\
	 */
	public function run() {
		$appPath = $this->_loader->getPathByByNamespace($this->_appNamespace);
		if ( false === $appPath ) {
			throw new \Exception("appPath of {$this->_appNamespace} is not registered!");
		}
		
		//init config
		$this->_initConfig();

		//初始化协程调度器
		$this->_initScheduler();
		
		return $this;
	}
	
}