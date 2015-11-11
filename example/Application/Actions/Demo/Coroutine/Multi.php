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
namespace Application\Actions\Demo\Coroutine;
use \Aha\Mvc\Action;

class Multi extends Action {
	
	public function excute() {
		$response	= $this->_objDispatcher->getResponse();
		
		$objFetch = new \Application\Models\Coroutine\Fetch();
		$data = (yield $objFetch->getFromMulti()) ;
		
//		if ( isset($data['data']['length']['data']['body']) ) {
//			$response->end($data['data']['length']['data']['body']);
//		} else {
//			$response->end(json_encode($data['data']['length']));
//		}
		
		if ( isset($data['data']['trunked']['data']['body']) ) {
			$response->end($data['data']['trunked']['data']['body']);
		} else {
			$response->end(json_encode($data['data']['trunked']));
		}
	}
	
} 