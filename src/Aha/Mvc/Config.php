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
namespace Aha\Mvc;

class Config {
	
	//aha框架bootstrap的实例
	private $_objBootstrap = null;

	//所有的配置项
	private $_arrConfig		= array();
	
	/**
	 * @brief 初始化Config配置
	 * @param \Aha\Mvc\Aha\Bootstrap $objBootstrap
	 * @return \Aha\Mvc\Config
	 */
	public function __construct(Aha\Bootstrap $objBootstrap) {
		$this->_objBootstrap = $objBootstrap;
		$this->_initConfig();
		return $this;
	}
	
	/**
	 * @brief 遍历所有配置文件 初始化配置
	 * @return type
	 */
	protected function _initConfig() {
		$appNamespace	= $this->_objBootstrap->getAppNamespace();
		$appPath		= $this->_objBootstrap->getPathByByNamespace($appNamespace);
		$configPath		= $appPath . DIRECTORY_SEPARATOR . 'Config';
		if ( !is_dir($configPath) ) {
			return;
		}
		
		$this->_loadConfig($configPath);
		
		$environ			= $this->_objBootstrap->getEnviron();
		$environConfigPath	= $configPath . DIRECTORY_SEPARATOR . $environ;
		if ( !is_dir($environConfigPath) ) {
			return;
		}
		
		$this->_loadConfig($environConfigPath);
	}
	
	/**
	 * @brief load config配置文件
	 * @param string $path
	 * @return type
	 */
	protected function _loadConfig(string $path) {
		$direcotryIterator = new \DirectoryIterator($path);
		foreach ( $direcotryIterator as $iteratorItem ) {
			if ( ! ( $iteratorItem->isFile() && substr($iteratorItem->getFilename(), -4) === '.php' ) ) {
				continue;
			}
			$file		= $path . DIRECTORY_SEPARATOR . $iteratorItem->getFilename();
			$section	= strtolower(basename($iteratorItem->getFilename(), '.php'));
			if ( !isset($this->_arrConfig[$section]) ) {
				$this->_arrConfig[$section] = require $file;
				continue;
			}
			$arrConfig = require $file;
			$this->_arrConfig[$section] = array_merge( $this->_arrConfig[$section], $arrConfig );
		}
		return;
	}
	
	/**
	 * @brief 获取配置项的值
	 * @param string $section
	 * @param string $key
	 * @return type
	 */
	public function get(string $section, string $key = '') {
		$arrConfig = isset($this->_arrConfig[$section]) ? $this->_arrConfig[$section] : array();
		if ( empty($key) ) {
			return $arrConfig;
		}
		return isset($arrConfig[$key]) ? $arrConfig[$key] : null;
	}
	
	/**
	 * @brief 用对象的方式获取配置项
	 * @param string $name
	 * @return type
	 */
	public function __get(string $name) {
		$arrConfig = isset($this->_arrConfig[$name]) ? $this->_arrConfig[$name] : array();
		return (object) $arrConfig;
	}
	
}