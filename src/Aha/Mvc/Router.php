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
	
	const URI_MAX_DEPTH	= 4;
	
	//application instance
	private $_objBootstrap = null;
	//路由uri
	private $_uri = '';
	//路由分隔符
	private $_delimiter = '/';
	//action class name
	private $_action	=	null;
	//invoke method
	private $_method	=	null;

	/**
	 * @brief 路由初始化
	 * @param \Aha\Mvc\Aha\Bootstrap $bootstrap
	 * @param string $uri
	 * @param string $delimeter
	 * @return \Aha\Mvc\Router
	 */
	public function __construct(Aha\Bootstrap $bootstrap, string $uri, string $delimeter='/') {
		$this->_objBootstrap = $bootstrap;
		$this->_uri			= str_replace(array(' ','.'),'',$uri);
		$this->_delimiter	= $delimeter;
		$this->_route();
		return $this;
	}
	
	/**
	 * @brief 路由解析
	 * @return type
	 * @throws Exception
	 */
	protected function _route() {
		if ( empty($this->_uri) ) {
			$this->_action = "${appNamespace}\\Actions\Index";
			$this->_method = 'Index';
			return;
		}
		
		if ( !preg_match('/^[\w-]+$/', $this->_uri) ) {
			throw new Exception("invalid uri {$this->_uri}");
		}
		
		$arrUriPatrs = array_map('ucfirst',array_filter(explode($this->_delimiter, $this->_uri)));
		if ( empty($arrUriParts) ) {
			$this->_action = "${appNamespace}\\Actions\Index";
			$this->_method = 'Index';
			return;
		}
		
		if ( count($arrUriParts) > self::URI_MAX_DEPTH ) {
			throw new Exception("uri {$this->_uri} is too long!");
		}
		
		$this->_detect($arrUriParts);
	}
	
	/**
	 * @brief 路由尝试及文件检测
	 * @param type $arrUriParts
	 * @return type
	 * @throws \Exception
	 */
	protected function _detect($arrUriParts) {
		$appNamespace = $this->_objBootstrap->getAppNamespace();
		$appPath	  = $this->_objBootstrap->getLoader()->getPathByByNamespace($appNamespace);
		array_unshift($arrUriParts, $appNamespace);
		//达到最长度限制的只能是这样 最后一个元素是method 倒数第二个元素是action
		if (count($arrUriParts) === self::URI_MAX_DEPTH + 1 ) {
			$method = array_pop($arrUriParts);
			$actionPath = explode(DIRECTORY_SEPARATOR, $arrUriParts);
			if ( !file_exists($appPath . DIRECTORY_SEPARATOR . $actionPath . AHA_EXT) ) {
				throw new \Exception("uri {$this->_uri} not found");
			}
			$this->_action = explode('\\', $arrUriParts);
			$this->_method = $method;
			return;
		}
		
		//首先尝试method为Index的情况(同级目录中文件名和目录名相同 目录优先，找Index.php)
		$actionPath = explode(DIRECTORY_SEPARATOR, $arrUriParts);
		if ( file_exists($appPath . DIRECTORY_SEPARATOR . $actionPath . AHA_EXT) ) {
			$this->_action = explode('\\', $arrUriParts);
			$this->_method = 'Index';
			return;
		}
		
		//self::URI_MAX_DEPTH-1 个长度事 index尝试未成功 直接抛异常
		if (count($arrUriParts) === self::URI_MAX_DEPTH ) {
				throw new \Exception("uri {$this->_uri} not found");
		}
		
		//尝试最后一个元素为method 倒数第二个为action
		$method = array_pop($arrUriParts);
		if ( file_exists($appPath . DIRECTORY_SEPARATOR . $actionPath . AHA_EXT) ) {
			$this->_action = explode('\\', $arrUriParts);
			$this->_method = $method;
			return;
		}
		
		//尝试action和目录均为缺省的情况
		array_push($arrUriParts,$method, 'Index');
		$actionPath = explode(DIRECTORY_SEPARATOR, $arrUriParts);
		if ( file_exists($appPath . DIRECTORY_SEPARATOR . $actionPath . AHA_EXT) ) {
			$this->_action = explode('\\', $arrUriParts);
			$this->_method = 'Index';
			return;
		}
		
		throw new \Exception("uri {$this->_uri} not found");
	}
	
}