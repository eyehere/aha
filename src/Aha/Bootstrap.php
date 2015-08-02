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

namespace Ala;

define('AHA_PATH', dirname(__DIR__));

class Bootstrap {
	
	//当前application的名字
	private $_appNamespace = null;
	//部署环境
	private $_environ = 'product';
	//loader instance
	private $_loader = null;
	
	//application instance
	private static $_instance = null;
	
	/**
	 * @brief application 单例 
	 * @param string $appNamespace
	 * @param string $environ
	 * @return type
	 */
	public static function getInstance(string $appNamespace, string $environ = 'product') {
		if ( null === self::$_instance ) {
			self::$_instance = new Aha\Bootstrap($appNamespace, $environ);
		}
		return self::$_instance;
	}
	
	/**
	 * @brief 应用引导程序初始化
	 * @param string $appNamespace
	 * @param string $environ
	 * @return \Ala\Bootstrap
	 */
	public function __construct(string $appNamespace, string $environ = 'product') {
		$this->_appNamespace	= $appNamespace;
		$this->_environ			= $environ;
		$this->_initLoader();
		return $this;
	}
	
	/**
	 * @brief 初始化自动加载器
	 */
	protected function _initLoader() {
		require_once AHA_PATH . '/Aha/Mvc/Loader.php';

		$this->_loader = \Aha\Mvc\Loader::getInstance();
		$this->_loader->registerNamespace('Aha', AHA_PATH);

		spl_autoload_register( array($this->_loader, 'autoload') );
	}
	
	
	protected function _initConfig() {
		
	}


	protected function _initFilter() {
		
	}
	
	protected function _initDispatcher() {
		
	}

	/**
	 * @brief 获取自动加载器实例
	 * @return type
	 */
	public function getLoader() {
		return $this->_loader;
	}
	
	/**
	 * @brief 获取部署环境
	 * @return type
	 */
	public function getEnviron() {
		return $this->_environ;
	}

	/**
	 * @brief application run main function
	 * @return \Ala\Bootstrap
	 * @throws Exception
	 */
	public function run() {
		$appPath = $this->_loader->getPathByByNamespace($this->_appNamespace);
		if ( false === $appPath ) {
			throw new Exception("appPath of {$this->_appNamespace} is not registered!");
		}
		
		//init config
		$this->_initConfig();
		
		//init filter
		$this->_initFilter();
		
		//init dispatcher
		$this->_initDispatcher();
		
		return $this;
	}
	
}