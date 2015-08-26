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
namespace Application\Actions\Demo\Storage;
use \Aha\Mvc\Action;

class Rdb extends Action {
	
	public function excute() {
		$config = $this->_objDispatcher->getBootstrap()->getConfig();
		$instanceName = 'test';
		$conf = $config->get('redis', $instanceName);
		$conn = \Aha\Storage\Memory\Pool::getConnection($instanceName, $conf);

		//$conn->zadd('sortedSet', 10, 'aaaa', 20, 'bbbbb', array($this, 'QueryCallback'));
		//$conn->zrange('sortedSet',0, -1, 'WITHSCORES',array($this, 'QueryCallback'));
		
		//$conn->sadd('settest', 10, 'aaaa', 20, 'bbbbb', array($this, 'QueryCallback'));
		//$conn->smembers('settest', array($this, 'QueryCallback'));
		
		//$conn->rpush('listtest', 12, 'aaaa', 27, 'bbbbb', array($this, 'QueryCallback'));
		//$conn->lrange('listtest', 0, -1, array($this, 'QueryCallback'));
		
		//$conn->hmset('ms',  array('a'=>'12345','b'=>'wqerty'), array($this, 'QueryCallback'));
		//$conn->hmget('ms',  array('a','b'), array($this, 'QueryCallback'));
		//$conn->hgetall('ms', array($this, 'QueryCallback'));
		
		//$conn->set('setA',  str_repeat('A', 20), array($this, 'QueryCallback'));
		//$conn->mset('setB',  str_repeat('B', 20), 'setC', str_repeat('C', 20), array($this, 'QueryCallback'));
		//$conn->mget('setB', 'setC', array($this, 'QueryCallback'));
		$conn->get('setA', array($this, 'QueryCallback'));
	}
	
	public function QueryCallback($result, $error) {
		$request	= $this->_objDispatcher->getRequest();
		$response	= $this->_objDispatcher->getResponse();
		
		$arrData = compact('result','error');
		$response->end(json_encode($arrData));
	}
	
}