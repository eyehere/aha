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
	
	//HTTP BEGIN=========================================================
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

	//TCP BEGIN========================================================
	/**
	 * @brief tcp 长连接连接池
	 * @var hashTable 
	 */
	protected static $_tcpPools = array();
	
	/**
	 * @brief tcp 长连接连接池连接数
	 * @var type 
	 */
	protected static $_tcpConnNum	=	array();
	
	/**
	 * @brief tcp 长连接连接池大小
	 * @var type 
	 */
	protected static $_tcpPoolSize	= array();

	/**
	 * @brief 全局的client poll,主要用于触发GC
	 * @var hashTable 
	 */
	protected static $_gcTcpPools = array();
	
	//UDP BEGIN========================================================
	/**
	 * @brief udp 长连接连接池
	 * @var hashTable 
	 */
	protected static $_udpPools = array();
	
	/**
	 * @brief udp 长连接连接池连接数
	 * @var type 
	 */
	protected static $_udpConnNum	=	array();
	
	/**
	 * @brief udp 长连接连接池大小
	 * @var type 
	 */
	protected static $_udpPoolSize	= array();

	/**
	 * @brief 全局的client poll,主要用于触发GC
	 * @var hashTable 
	 */
	protected static $_gcUdpPools = array();
	
	//HTTP BEGIN========================================================
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
	public static function getHttpClient( $method, $url, $poolSize = 200, $timeout = 1, $connectTimeout = 0.05) {
		//gc
		if ( !empty(self::$_gcPools) ) {
			foreach (self::$_gcPools as $key=>$client) {
				unset(self::$_gcPools[$key], $client);
			}
		}
		//优先从连接池中获取
		$arrUrl	= parse_url($url);
		$host	= $arrUrl['host'];
		$port	= isset($arrUrl['port']) ? $arrUrl['port'] : 80;
		if ( $arrUrl['scheme'] === 'https' ) {
			$port = 443;
		}
		$poolName = $host . ':' . $port;
		
		if ( !empty(self::$_httpPools[$poolName]) ) {
			$httpCli = array_shift(self::$_httpPools[$poolName]);
			return self::_decorateHttpClient($httpCli, $method, $url);
		}
		//如果当前连接数大于连接池大小 跑异常
		//http client 不做queue是因为http请求每个连接请求占用的时间相对比较长，应该控制好相对的连接池大小
		//queue可能会是相应时间更长 系统恶化
		self::$_httpPoolSize[$poolName] = $poolSize;
		if ( !isset(self::$_httpConnNum[$poolName]) ) {
			self::$_httpConnNum[$poolName] = 0;
		}
		
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
	
	//TCP BEGIN============================================================
	/**
	 * 释放 tcp_client 连接进入连接池
	 * @param \Aha\Network\Client $client
	 */
	public static function freeTcp($poolName,\Aha\Network\Client $client) {
		if ( !isset(self::$_tcpPools[$poolName]) ) {
			self::$_tcpPools[$poolName] = array();
		}
		self::$_tcpPools[$poolName][] = $client;
	}
	
	/**
	 * @brief 获取tcp client的实例
	 * @param  $method
	 * @param  $url
	 * @param  $timeout
	 * @param \float $connectTimeout
	 * @return \Aha\Client\Tcp
	 */
	public static function getTcpClient( $host, $port, $poolSize = 200, $timeout = 1, $connectTimeout = 0.05) {
		//gc
		if ( !empty(self::$_gcTcpPools) ) {
			foreach (self::$_gcTcpPools as $key=>$client) {
				unset(self::$_gcTcpPools[$key], $client);
			}
		}
		//优先从连接池中获取
		$poolName = $host . ':' . $port;
		var_dump(count(self::$_tcpPools[$poolName]));
		if ( !empty(self::$_tcpPools[$poolName]) ) {
			$tcpCli = array_shift(self::$_tcpPools[$poolName]);
			return $tcpCli;
		}
		//如果当前连接数大于连接池大小 跑异常
		//tcp client 不做queue是因为tcp请求每个连接请求占用的时间相对比较长，应该控制好相对的连接池大小
		//queue可能会是相应时间更长 系统恶化
		self::$_tcpPoolSize[$poolName] = $poolSize;
		if ( !isset(self::$_tcpConnNum[$poolName]) ) {
			self::$_tcpConnNum[$poolName] = 0;
		}
		
		if ( self::$_tcpConnNum[$poolName] >= self::$_tcpPoolSize[$poolName] ) {
			throw new \Exception("TcpClient of $poolName is beyond poolSize![$poolSize]");
		}
		
		self::$_tcpConnNum[$poolName]++;
		$tcpCli = new \Aha\Client\Tcp($host, $port, $timeout, $connectTimeout);
		return $tcpCli;
	}

	/**
	 * 释放 tcp_client gc
	 * @param \Aha\Network\Client $client
	 */
	public static function gcTcp($poolName,\Aha\Network\Client $client) {
		self::$_tcpConnNum[$poolName]++;
		self::$_gcTcpPools[] = $client;
	}
	
	//UDP BEGIN===========================================================
	/**
	 * 释放 udp_client 连接进入连接池
	 * @param \Aha\Network\Client $client
	 */
	public static function freeUdp($poolName,\Aha\Network\Client $client) {
		if ( !isset(self::$_udpPools[$poolName]) ) {
			self::$_udpPools[$poolName] = array();
		}
		self::$_udpPools[$poolName][] = $client;
	}
	
	/**
	 * @brief 获取udp client的实例
	 * @param  $method
	 * @param  $url
	 * @param  $timeout
	 * @param \float $connectTimeout
	 * @return \Aha\Client\Udp
	 */
	public static function getUdpClient( $host, $port, $poolSize = 200, $timeout = 1, $connectTimeout = 0.05) {
		//gc
		if ( !empty(self::$_gcUdpPools) ) {
			foreach (self::$_gcUdpPools as $key=>$client) {
				unset(self::$_gcUdpPools[$key], $client);
			}
		}
		//优先从连接池中获取
		$poolName = $host . ':' . $port;
		var_dump(count(self::$_udpPools[$poolName]));
		if ( !empty(self::$_udpPools[$poolName]) ) {
			$udpCli = array_shift(self::$_udpPools[$poolName]);
			return $udpCli;
		}
		//如果当前连接数大于连接池大小 跑异常
		//udp client 不做queue是因为udp请求每个连接请求占用的时间相对比较长，应该控制好相对的连接池大小
		//queue可能会是相应时间更长 系统恶化
		self::$_udpPoolSize[$poolName] = $poolSize;
		if ( !isset(self::$_udpConnNum[$poolName]) ) {
			self::$_udpConnNum[$poolName] = 0;
		}
		
		if ( self::$_udpConnNum[$poolName] >= self::$_udpPoolSize[$poolName] ) {
			throw new \Exception("UdpClient of $poolName is beyond poolSize![$poolSize]");
		}
		
		self::$_udpConnNum[$poolName]++;
		$udpCli = new \Aha\Client\Udp($host, $port, $timeout, $connectTimeout);
		return $udpCli;
	}

	/**
	 * 释放 udp_client gc
	 * @param \Aha\Network\Client $client
	 */
	public static function gcUdp($poolName,\Aha\Network\Client $client) {
		self::$_udpConnNum[$poolName]++;
		self::$_gcUdpPools[] = $client;
	}
	
}