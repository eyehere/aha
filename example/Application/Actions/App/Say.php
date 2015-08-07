<?php
/*
  +----------------------------------------------------------------------+
  | Application                                                                  |
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
namespace Application\Actions\App;
use \Aha\Mvc\Action;

class Say extends Action {
	
	public function excute() {
		$request	= $this->_objDispatcher->getRequest();
		$response	= $this->_objDispatcher->getResponse();
		$response->end("Welcome to the world of Aha!");
		/*
		$config		= $this->_objDispatcher->getBootstrap()->getConfig();
		$data = array(
			'get' => $config->get('database','key'),
			'obj' => $config->database->key,
			'all' => $config->get('database'),
		);
		$response->write(json_encode($data));
		$response->end();
		 */
	}
	
} 