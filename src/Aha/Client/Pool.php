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
	 * @brief httpclient keep-alive 长连接连接池
	 * @var hashTable 
	 */
	protected static $_httpPools = array();
	
	/**
	 * @brief httpclient keep-alive 长连接连接池连接数
	 * @var type 
	 */
	protected static $_httpConnNum	=	array();
	
	/**
	 * @brief httpclient keep-alive 长连接连接池大小
	 * @var type 
	 */
	protected static $_httpPoolSize	= array();

	/**
	 * @brief 全局的client poll,主要用于触发GC
	 * @var hashTable 
	 */
	protected static $_gcPools = array();

	/**
	 * 释放 http_client keep-alive连接进入连接池
	 * @param \Aha\Network\Client $client
	 */
	public static function freeHttp($poolName,\Aha\Network\Client $client) {
		if ( !isset(self::$_httpPools[$poolName]) ) {
			self::$_httpPools[$poolName] = array();
		}
		self::$_httpPools[$poolName][] = $client;
	}
	
	/**
	 * @brief 获取http client的实例
	 * @param  $method
	 * @param  $url
	 * @param  $timeout
	 * @param \float $connectTimeout
	 * @return \Aha\Client\Http
	 */
	public static function getHttpClient( $method, $url, $poolSize, $timeout = 1, $connectTimeout = 0.05) {
		//gc
		if ( !empty(self::$_gcPools) ) {
			foreach (self::$_gcPools as $key=>$client) {
				unset(self::$_gcPools[$key], $client);
			}
		}
		//优先从连接池中获取
		$poolName = parse_url($url, PHP_URL_HOST).':'.parse_url($url, PHP_URL_PORT);
		if ( empty(self::$_httpPools[$poolName]) ) {
			$httpCli = array_shift(self::$_httpPools[$poolName]);
			return self::_decorateHttpClient($httpCli, $method, $url);
		}
		//如果当前连接数大于连接池大小 跑异常
		//http client 不做queue是因为http请求每个连接请求占用的时间相对比较长，应该控制好相对的连接池大小
		//queue可能会是相应时间更长 系统恶化
		self::$_httpPoolSize[$poolName] = $poolSize;
		if ( self::$_httpConnNum[$poolName] >= self::$_httpPoolSize[$poolName] ) {
			throw new \Exception("HttpClient of $poolName is beyond poolSize![$poolSize]");
		}
		
		self::$_httpConnNum[$poolName]++;
		$httpCli = new \Aha\Client\Http($method, $url, $timeout, $connectTimeout);
		return self::_decorateHttpClient($httpCli, $method, $url);
	}
	
	/**
	 * @brief 初始化http client
	 * @param type $httpCli
	 * @param type $method
	 * @param type $url
	 * @return type
	 */
	protected static function _decorateHttpClient($httpCli, $method, $url) {
		$httpCli->setMethod($method)->setUrl($url)->init();
		return $httpCli;
	}

	/**
	 * 释放 http_client gc
	 * @param \Aha\Network\Client $client
	 */
	public static function gcHttp($poolName,\Aha\Network\Client $client) {
		self::$_httpConnNum[$poolName]++;
		self::$_gcPools[] = $client;
	}
	
}