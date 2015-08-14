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
namespace Application\Actions\Demo\Client;
use \Aha\Mvc\Action;
use \Aha\Client\Udp;

class Udp extends Action {
	
	public function excute() {
		$tcpCli = new Udp('10.10.8.172','9603');
		$tcpCli->setRequestId('UdpRequest');
		$tcpCli->setCallback( array($this, 'output') );
		$arrDara = array(
			'cmd' => 'demo-server-udp',
			'body'=> 'from http request'
		);
		$tcpCli->setPackage(json_encode($arrDara));
		$tcpCli->loop();
	}
	
	public function output($data) {
		$request	= $this->_objDispatcher->getRequest();
		$response	= $this->_objDispatcher->getResponse();
		
		$response->end(json_encode($data));
	}
	
} 