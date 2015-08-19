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
namespace Aha\Client;

class Pool {
	
	/**
	 * @brief 全局的client poll,主要用于触发GC
	 * @var hashTable 
	 */
	protected static $_httpPools = array();
	
	/**
	 * 释放 http_client
	 * @param \Aha\Network\Client $client
	 */
	public static function freeHttp(\Aha\Network\Client $client) {
		self::$_httpPools[] = $client;
	}
	
	/**
	 * @brief 获取http client的实例
	 * @param  $method
	 * @param  $url
	 * @param  $timeout
	 * @param \float $connectTimeout
	 * @return \Aha\Client\Http
	 */
	public static function getHttpClient( $method, $url, $timeout = 1, $connectTimeout = 0.05) {
		if ( !empty(self::$_httpPools) ) {
			foreach (self::$_httpPools as $key=>$client) {
				unset(self::$_httpPools[$key]);
			}
		}
		return new \Aha\Client\Http($method, $url, $timeout, $connectTimeout);
	}
	
}