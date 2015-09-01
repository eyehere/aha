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
namespace Application\Models\Coroutine;

class Fetch {
	
	public function getMeituPage() {
		$http = \Aha\Client\Pool::getHttpClient('GET', 'http://www.meitu.com/');
		yield ( $http->setRequestId('contentLength') );
	}
	
	public function getFromTcp() {
		$tcpCli = \Aha\Client\Pool::getTcpClient('10.10.8.172','9602');
		$tcpCli->setRequestId('TcpRequest');
		$arrDara = array(
			'cmd' => 'demo-server-tcp',
			'body'=> 'from http request'
		);
		yield ( $tcpCli->setPackage(json_encode($arrDara)) );
	}
	
	public function getFromUdp() {
		$tcpCli = \Aha\Client\Pool::getUdpClient('10.10.8.172','9603');
		$tcpCli->setRequestId('UdpRequest');
		$arrDara = array(
			'cmd' => 'demo-server-udp',
			'body'=> 'from http request'
		);
		yield ( $tcpCli->setPackage(json_encode($arrDara)) );
	}
	
	public function getFromMulti() {
		$http1 = \Aha\Client\Pool::getHttpClient('GET', 'http://www.qq.com/');
		$http1->setRequestId('trunked');
		$http2 = \Aha\Client\Pool::getHttpClient('GET', 'http://www.jd.com/');
		$http2->setRequestId('length');
		$mutli = new \Aha\Client\Multi();
		$mutli->register($http1);
		yield ( $mutli->register($http2) );
	}
	
}