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
namespace Application\Actions\Service\Monitor;
use \Aha\Mvc\Action;

class Stats extends Action {
	
	public function excute() {
		$arrData = array(
			'local_ip'	=> swoole_get_local_ip(),
			'version'	=> swoole_version(),
			'setting'	=> $this->_objDispatcher->getBootstrap()->getServer()->setting,
			'stats'		=> $this->_objDispatcher->getBootstrap()->getServer()->stats(),
		);
		$response	= $this->_objDispatcher->getResponse();
		$response->end( json_encode($arrData) );
	}
	
} 