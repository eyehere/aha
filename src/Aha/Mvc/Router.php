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

class Router {
	
	const URI_MAX_DEPTH	= 3;
	
	//application instance
	private $_objBootstrap = null;
	//路由uri
	private $_uri = '';
	//路由分隔符
	private $_delimiter = '/';
	//action class name
	private $_action	=	null;
	//invoke method
	private $_method	=	'execute';
	
	//actions是否被校验过
	private static $_arrActions = array();

	/**
	 * @brief 路由初始化
	 * @param \Aha\Mvc\Aha\Bootstrap $bootstrap
	 * @param string $uri
	 * @param string $delimeter
	 * @return \Aha\Mvc\Router
	 */
	public function __construct(\Aha\Bootstrap $bootstrap, \string $uri, \string $delimeter='/') {
		$this->_objBootstrap = $bootstrap;
		$this->_uri			= trim(str_replace(array(' ','.'),'',$uri), "${delimeter}/");
		$this->_delimiter	= $delimeter;
		return $this;
	}
	
	/**
	 * @brief 获取路由完成后的action类名
	 * @return type
	 */
	public function getAction() {
		return $this->_action;
	}
	
	/**
	 * @brief 获取路由完成后调用的方法名
	 * @return type
	 */
	public function getMethod() {
		return $this->_method;
	}
	
	/**
	 * @brief 路由解析
	 * @return type
	 * @throws \Exception\
	 */
	public function route() {
		$appNamespace = $this->_objBootstrap->getAppNamespace();
		$appPath	  = $this->_objBootstrap->getLoader()->getPathByByNamespace($appNamespace);
		
		$defaultAction = implode(DIRECTORY_SEPARATOR, array($appPath, $appNamespace, 'Actions', 'Index', 'Index'));
		
		if ( empty($this->_uri) ) {
			$this->_action = "\\${appNamespace}\\Actions\\Index\\Index\\Index";
			if ( \Aha\Mvc\Router::validate($defaultAction) ) {
				throw new \Exception("default uri {$this->_uri} not found");
			}
			return;
		}
		
		if ( !preg_match('/^[\w\/-]+$/', $this->_uri) ) {
			throw new \Exception("invalid uri {$this->_uri}");
		}
		
		$arrUriParts = array_map('ucfirst',array_filter(explode($this->_delimiter, $this->_uri)));
		if ( empty($arrUriParts) ) {
			$this->_action = "\\${appNamespace}\\Actions\\Index\\Index\\Index";
			if ( \Aha\Mvc\Router::validate($defaultAction) ) {
				throw new \Exception("default uri {$this->_uri} not found");
			}
			return;
		}
		
		if ( count($arrUriParts) > self::URI_MAX_DEPTH ) {
			throw new \Exception("uri {$this->_uri} is too long!");
		}
		
		array_unshift($arrUriParts, 'Actions');
		
		$this->_detect($arrUriParts);
	}
	
	/**
	 * @brief 递归的进行最大深度的尝试和遍历 进行路由检测
	 * @param array $arrElements
	 * @param string $append
	 * @return type
	 * @throws \Exception
	 */
	protected function _detect(array $arrElements, \string $append = '' ) {
		$appNamespace = $this->_objBootstrap->getAppNamespace();
		$appPath	  = $this->_objBootstrap->getLoader()->getPathByByNamespace($appNamespace);
		
		if ( !empty($append) ) {
			array_push($arrElements, $append);
		}
		$arrUriParts  = $arrElements;
		array_unshift($arrUriParts, $appNamespace);
		
		$actionPath = implode(DIRECTORY_SEPARATOR, $arrUriParts);
		if ( in_array($appPath . DIRECTORY_SEPARATOR . $actionPath, self::$_arrActions) ) {
			$this->_action = '\\' . implode('\\', $arrUriParts);
			return;
		}
		
		if (count($arrUriParts) === self::URI_MAX_DEPTH ) {
			throw new \Exception("uri {$this->_uri} not found");
		}
		
		return $this->_detect($arrElements, 'Index');
	}
	
	
	/**
	 * @brief 迭代出actions目录的所有action文件 
	 * 这样在做路由的时候 检测路由是hash操作 不会有文件检测的io操作 更高效更快速
	 * @param \Aha\Mvc\Aha\Bootstrap $bootstrap
	 */
	public static function loadActionPaths(\Aha\Bootstrap $bootstrap) {
		$appNamespace = $bootstrap->getAppNamespace();
		$appPath	  = $bootstrap->getLoader()->getPathByByNamespace($appNamespace);
		$actionPath	  = $appPath . DIRECTORY_SEPARATOR . $appNamespace . DIRECTORY_SEPARATOR . 'Actions';
		
		self::$_arrActions = array();
		
		$directoryIt = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($actionPath));
		$directoryIt->rewind();
		while ($directoryIt->valid()) {
			if ( !$directoryIt->isDot() && substr($directoryIt->key(), -4) === AHA_EXT ) {
				array_push(self::$_arrActions, rtrim($directoryIt->key(), AHA_EXT));
			}
			$directoryIt->next();
		}
	}
	
	
	/**
	 * @brief 校验action文件是否存在
	 * @param string $actionPath
	 * @return boolean
	 */
	public static function validate(\string $actionPath) {
		if ( !in_array($actionPath, self::$_arrActions) ) {
			return false;
		}
		return true;
	}
	
}