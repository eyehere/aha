<?php
/*
  +----------------------------------------------------------------------+
  | Application                                                          |
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
namespace Application\Actions\Demo\Client;
use \Aha\Mvc\Action;
use \Aha\Client\Multi;

class Http extends Action {
	
	public function excute() {
		
//		$http1 = \Aha\Client\Pool::getHttpClient('GET', 'http://www.qq.com/');
//		$http1->setRequestId('trunked');
//		$http2 = \Aha\Client\Pool::getHttpClient('GET', 'http://www.jd.com/');
//		$http2->setRequestId('length');
//		$mutli = new Multi();
//		$mutli->register($http1);
//		$mutli->register($http2);
//		$mutli->loop(array($this,'output'));
		
		$http = \Aha\Client\Pool::getHttpClient('GET', 'http://www.jd.com/');
		$http->setRequestId('contentLength');
		$http->setCallback( array($this, 'output') );
		$http->loop();
	}
	
	public function output($data) {
		$request	= $this->_objDispatcher->getRequest();
		$response	= $this->_objDispatcher->getResponse();
		
		if ( isset($data['data']['body']) ) {
			$response->end($data['data']['body']);
		} else {
			$response->end(json_encode($data));
		}
		
//		if ( isset($data['data']['length']['data']['body']) ) {
//			$response->end($data['data']['length']['data']['body']);
//		} else {
//			$response->end(json_encode($data['data']['length']));
//		}
		
//		if ( isset($data['data']['trunked']['data']['body']) ) {
//			$response->end($data['data']['trunked']['data']['body']);
//		} else {
//			$response->end(json_encode($data['data']['trunked']));
//		}
	}
	
	public function __destruct() {
		//var_dump(__METHOD__);
	}
	
} 
