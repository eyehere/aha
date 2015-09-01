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
	
	public function getFromDb($dispatcher) {
		$config = $dispatcher->getBootstrap()->getConfig();
		$dbName = 'test';
		$dbConf = $config->get('database', $dbName);
		$conn = \Aha\Storage\Db\Pool::getConnection($dbName, $dbConf);
		yield ( $conn->createCoroutine()
			 ->query("select * from friends limit 10") );
	}
	
	public function dbTrans($dispatcher) {
		$config = $dispatcher->getBootstrap()->getConfig();
		$dbName = 'test';
		$dbConf = $config->get('database', $dbName);
		$conn = \Aha\Storage\Db\Pool::getConnection($dbName, $dbConf);
		$trans = $conn->beginTrans()
				->queue('user','insert into user set name="Aha",phone="15801228065"')
				->queue('friends',function($result){
					$friendId = intval($result['user']['last_insert_id']);
					$sql = 'insert into friends set user_id=6,friend_id='.$friendId;
					return $sql;
				})
				->queue('friendsPlus','insert into friends set user_id=100000,friend_id=1000000');
		yield ( $trans );			
	}
	
	public function redisDemo($dispatcher) {
		$config = $dispatcher->getBootstrap()->getConfig();
		$instanceName = 'test';
		$conf = $config->get('redis', $instanceName);
		$conn = \Aha\Storage\Memory\Pool::getConnection($instanceName, $conf);

		$res1 = yield ( $conn->createCoroutine()->hmset('ms',  array('a'=>'12345','b'=>'wqerty')) );
		$res2 = yield ( $conn->createCoroutine()->hmget('ms',  array('a','b')) );
		$res3 = yield ( $conn->createCoroutine()->hgetall('ms') );
		
		yield ( compact('res1','res2','res3') );
	}
	
}