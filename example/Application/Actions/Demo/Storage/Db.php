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

class Db extends Action {
	
	public function excute() {
		$config = $this->_objDispatcher->getBootstrap()->getConfig();
		$dbName = 'test';
		$dbConf = $config->get('database', $dbName);
		$conn = \Aha\Storage\Db\Pool::getConnection($dbName, $dbConf);
		$conn->query("select * from friends limit 10", array($this, 'QueryDbCallback'));
	}
	
	public function QueryDbCallback($result, $dbObj, $dbSock) {
		$request	= $this->_objDispatcher->getRequest();
		$response	= $this->_objDispatcher->getResponse();
		
		$arrData = array();
		if ( false === $result ) {
			$arrData = array('query_error');
		} else {
			$arrData = $result->fetch_all(MYSQLI_ASSOC);
		}
		$response->end(json_encode($arrData));
	}
	
}