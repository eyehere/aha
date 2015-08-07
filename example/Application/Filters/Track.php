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
namespace Application\Filters;

class Track {
	
	public function __construct() {
		;
	}
	
	public function preRouterOne(\Aha\Mvc\Dispatcher $dispatcher, array $data = array()) {
		$arr = array(
			'data'	=>  $data,
			'fn'	=>  __METHOD__
		);
		$response = $dispatcher->getResponse();
		echo json_encode($arr) . PHP_EOL;
		if ( isset($data['callback']) ) {
			call_user_func($data['callback'], $dispatcher, $data);
		}
	}
	
	public function preRouterTwo(\Aha\Mvc\Dispatcher $dispatcher, array $data = array()) {
		$arr = array(
			'data'	=>  $data,
			'fn'	=>  __METHOD__
		);
		$response = $dispatcher->getResponse();
		echo json_encode($arr) . PHP_EOL;
		if ( isset($data['callback']) ) {
			call_user_func($data['callback'], $dispatcher, $data);
		}
	}
	
	public function postRouterOne(\Aha\Mvc\Dispatcher $dispatcher, array $data = array()) {
		$arr = array(
			'data'	=>  $data,
			'fn'	=>  __METHOD__
		);
		$response = $dispatcher->getResponse();
		echo json_encode($arr) . PHP_EOL;
		if ( isset($data['callback']) ) {
			call_user_func($data['callback'], $dispatcher, $data);
		}
	}
	
	public function postRouterTwo(\Aha\Mvc\Dispatcher $dispatcher, array $data = array()) {
		$arr = array(
			'data'	=>  $data,
			'fn'	=>  __METHOD__
		);
		$response = $dispatcher->getResponse();
		echo json_encode($arr) . PHP_EOL;
		if ( isset($data['callback']) ) {
			call_user_func($data['callback'], $dispatcher, $data);
		}
	}
	
	public function preDispatchOne(\Aha\Mvc\Dispatcher $dispatcher, array $data = array()) {
		$arr = array(
			'data'	=>  $data,
			'fn'	=>  __METHOD__
		);
		$response = $dispatcher->getResponse();
		echo json_encode($arr) . PHP_EOL;
		if ( isset($data['callback']) ) {
			call_user_func($data['callback'], $dispatcher, $data);
		}
	}
	
	public function preDispatchTwo(\Aha\Mvc\Dispatcher $dispatcher, array $data = array()) {
		$arr = array(
			'data'	=>  $data,
			'fn'	=>  __METHOD__
		);
		$response = $dispatcher->getResponse();
		echo json_encode($arr) . PHP_EOL;
		if ( isset($data['callback']) ) {
			call_user_func($data['callback'], $dispatcher, $data);
		}
	}
	
	public function postDispatchOne(\Aha\Mvc\Dispatcher $dispatcher, array $data = array()) {
		$arr = array(
			'data'	=>  $data,
			'fn'	=>  __METHOD__
		);
		$response = $dispatcher->getResponse();
		echo json_encode($arr) . PHP_EOL;
		if ( isset($data['callback']) ) {
			call_user_func($data['callback'], $dispatcher, $data);
		}
	}
	
	public function postDispatchTwo(\Aha\Mvc\Dispatcher $dispatcher, array $data = array()) {
		$arr = array(
			'data'	=>  $data,
			'fn'	=>  __METHOD__
		);
		$response = $dispatcher->getResponse();
		echo json_encode($arr) . PHP_EOL;
		if ( isset($data['callback']) ) {
			call_user_func($data['callback'], $dispatcher, $data);
		}
	}
	
}