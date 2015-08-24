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

class Trans extends Action {
	
	public function excute() {
		$config = $this->_objDispatcher->getBootstrap()->getConfig();
		$dbName = 'test';
		$dbConf = $config->get('database', $dbName);
		$conn = \Aha\Storage\Db\Pool::getConnection($dbName, $dbConf);
		$conn->beginTrans()
				->queue('user','insert into user set name="Aha",phone="15801228065"')
				->queue('friends',function($result){
					$friendId = intval($result['user']['last_insert_id']);
					$sql = 'insert into friends set user_id=6,friend_id='.$friendId;
					return $sql;
				})
				->queue('friendsPlus','insert into friends set user_id=100000,friend_id=1000000')
				->setCallback(array($this, 'QueryDbCallback'))
				->execute();
	}
	
	public function QueryDbCallback($result, $dbObj, $dbSock) {
		$request	= $this->_objDispatcher->getRequest();
		$response	= $this->_objDispatcher->getResponse();
		
		$arrData = $result;
		if ( false === $result ) {
			$arrData = array('query_error');
		}
		$response->end(json_encode($arrData));
	}
	
}