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

class Loader {
	
	private static $_instance = null;
	
	private $_arrMap = array();
	
	/**
	 * @brief 实例化Loader
	 * @param type $namespace
	 * @param type $path
	 * @return \Aha\Mvc\Loader
	 */
	public function __construct(string $namespace = null, string $path = null) {
		if ( null !== $namespace && null !== $path ) {
			$this->_arrMap[$namespace] = $path;
		}
		return $this;
	}
	
	/**
	 * @brief Loader单例
	 * @param type $namespace
	 * @param type $path
	 * @return type
	 */
	public static function getInstance(string $namespace = null, string $path = null) {
		if ( null === self::$_instance ) {
			self::$_instance = new \Aha\Mvc\Loader($namespace, $path);
		}
		return self::$_instance;
	}
	
	/**
	 * @brief 注册命名空间和路劲的关系map
	 * @param string $namespace
	 * @param \Aha\Mvc\sring $path
	 * @return \Aha\Mvc\Loader
	 */
	public function registerNamespace(string $namespace, sring $path) {
		$this->_arrMap[$namespace] = $path;
		return $this;
	}
	
	/**
	 * @brief 获取namespace对应的path
	 * @param string $namespace
	 * @return boolean
	 */
	public function getPathByByNamespace(string $namespace) {
		if ( !isset($this->_arrMap[$namespace]) ) {
			return false;
		}
		return $this->_arrMap[$namespace];
	}
	
	/**
	 * @brief autoload 回调函数
	 * @param type $className
	 * @return type
	 * @throws Exception
	 */
	public function autoload($className) {
		$className = trim($className);
		if ( class_exists($className, false) || interface_exists($className) ) {
			return;
		}
		$className = trim($className, "\\");
		if ( empty($className) ) {
			throw new Exception("class name in empty!");
		}
		$classPatrs = array_map('ucfirst', explode('\\', $className));
		if ( !isset($this->_arrMap[$classPatrs[0]]) ) {
			throw new Exception("namespace {$classPatrs[0]} is not registered!");
		}
		array_unshift($classPatrs, $this->_arrMap[$classPatrs[0]]);
		$classFile = implode(DIRECTORY_SEPARATOR, $classPatrs);
		require_once $classFile;
	}
	
}