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
use \Aha\Network\Client;

class Http extends Client {
	
	protected $_method	= 'GET';//HTTP METHOD
	protected $_url		= null;//url
	
	protected $_requestHeaders	= array();//请求头
	protected $_query			= array();//Query String 请求get参数
	protected $_requestBody		= array();//Post Body 请求体

	protected $_host	= null;//host
	protected $_ip		= null;//IP
	protected $_port	= 80;//port
	protected $_path	= '/';

	protected $_responseHeader	= array();//响应头
	protected $_responseBody	= null;//响应body
	
	protected $_buffer		= '';//响应buffer
	protected $_trunkLength	= 0;//transfer-encoding:chunked时

	/**
	 * @brief 初始化一个http客户端
	 * @param  $method
	 * @param  $url
	 * @param type $timeout
	 * @param type $connectTimeout
	 */
	public function __construct( $method, $url, $timeout = 1, $connectTimeout = 0.05) {
		if ( !in_array($method, array('GET','POST')) ) {
			throw new Exception("unsupport http method {$method}");
		}
		$this->_method	= $method;
		$this->_url		= $url;

		$sockType = SWOOLE_SOCK_TCP;
		
		$scheme = parse_url($url, PHP_URL_SCHEME);
		if ( $scheme == 'https' ) {
			$sockType |= SWOOLE_SSL;
		}
		
		$client = new \swoole_client($sockType , SWOOLE_SOCK_ASYNC);
		parent::__construct($client, $this->_host, $this->_port, $timeout, $connectTimeout);
		
		$this->_initConfig();
	}
	
	/**
	 * @brief 初始化请求头 和请求的url相关信息
	 */
	public function init() {
		$arrUrl			= parse_url($this->_url);
		$this->_host	= $arrUrl['host'];
		if ( false !== ip2long($this->_host) ) {
			$this->_ip = $this->_host;
		}
		$this->_port	= isset($arrUrl['port']) ? $arrUrl['port'] : 80;
		if ( $arrUrl['scheme'] === 'https' ) {
			$this->_port = 443;
		}
		$this->_path = isset($arrUrl['path']) ? $arrUrl['path'] : '/';
		
		$this->_requestHeaders = array(
			'Host'				=> $this->_host,
			'User-Agent'		=> 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/34.0.1847.116 Safari/537.36',
			'Accept'			=> 'text/xml,application/xml,application/xhtml+xml,text/html,text/plain,image/png,image/jpeg,image/gif,*/*',
			'Accept-Language'	=> 'zh-CN,zh;q=0.8,en;q=0.6,zh-TW;q=0.4,ja;q=0.2',
			'Accept-Encoding'	=> 'deflate,sdch',
			'Connection'		=> 'keep-alive'
		);
		
		if ( function_exists('gzdecode') ) {
			$this->_requestHeaders['Accept-Encoding'] = 'gzip,deflate,sdch';
		}

		if ( $this->_method === 'POST' ) {
			$this->_requestHeaders['Content-Type'] = 'application/x-www-form-urlencoded';
		}
		
		return $this;
	}
	
	/**
	 * 初始化config 保证收到一个完整的Udp包再回调
	 */
	protected function _initConfig() {
		$setting = array(
			'open_tcp_nodelay'		=>  true
		);
		$this->_objClient->set($setting);
	}
	
	/**
	 * @brief 设置httpmethod
	 * @param type $method
	 * @return \Aha\Client\Http
	 * @throws Exception
	 */
	public function setMethod($method) {
		if ( !in_array($method, array('GET','POST')) ) {
			throw new Exception("unsupport http method {$method}");
		}
		$this->_method	= $method;
		return $this;
	}
	
	/**
	 * @brief 设置请求的url
	 * @param type $url
	 * @return \Aha\Client\Http
	 */
	public function setUrl($url) {
		$this->_url = $url;
		return $this;
	}

	/**
	 * @brief 设置query的参数
	 * @param array $query
	 * @return \Aha\Client\Http
	 */
	public function setQuery( array $query ) {
		$this->_query = $query;
		return $this;
	}
	
	/**
	 * @brief 设置请求的body
	 * @param array $body
	 * @return \Aha\Client\Http
	 */
	public function setRequestBody( array $body ) {
		$this->_requestBody = $body;
		return $this;
	}
	
	/**
	 * @brief 添加需要的http请求头
	 * @param array $headers
	 * @return \Aha\Client\Http
	 */
	public function setRequestHeaders(array $headers) {
		if ( empty($headers) ) {
			return $this;
		}
		$this->_requestHeaders = array_merge($this->_requestHeaders, $headers);
		return $this;
	}
	
	/**
	 * @brief build http request pachage
	 */
	protected function _buildRequest() {
		if ( !empty($this->_query) ) {
			$this->_path .= '?' . http_build_query($this->_query);
		}
		$body = '';
		if ( $this->_method === 'POST' && !empty($this->_requestBody) ) {
			$body = http_build_query($this->_requestBody);
			$this->_requestHeaders['Content-Length'] = strlen($body);
		}
		$header = "{$this->_method} {$this->_path} HTTP/1.1";
		foreach ( $this->_requestHeaders as $key=>$val ) {
			$header .= "\r\n{$key}:{$val}";
		}
		
		$this->setPackage( $header . "\r\n\r\n" . $body);
	}
	
	/**
	 * @brief 解析http响应头
	 * @return string
	 */
	protected function _unPackResponseHeader() {
		$responseParts = explode("\r\n\r\n", $this->_buffer, 2);
		$headerParts   = explode("\r\n", $responseParts[0]);
		list($this->_responseHeader['protocol'], 
			 $this->_responseHeader['status'], 
			 $this->_responseHeader['desc'] ) = explode(' ', $headerParts[0], 3);
		unset($headerParts[0]);
		
		foreach ( $headerParts as $headerPart ) {
			$headerPart = trim($headerPart);
			if ( empty($headerPart) ) continue;
			list($key, $val) = explode(':', $headerPart, 2);
			$this->_responseHeader[strtolower(trim($key))] = trim($val);
		}
		
		$this->_buffer = $responseParts[1];
		return AHA_DECLINED;
	}
	
	/**
	 * @brief 解析http response body
	 * @return string
	 */
	protected function _unPackResponseBody() {
		if ( isset($this->_responseHeader['content-length']) && 
			 $this->_responseHeader['content-length'] <= strlen($this->_buffer) ) {
			$this->_responseBody = $this->_buffer;
			return AHA_DECLINED;
		}
		
		if ( isset($this->_responseHeader['transfer-encoding']) &&
			 $this->_responseHeader['transfer-encoding'] === 'chunked' ) {
			return $this->_unPackChunkBody();
		}
		
		return AHA_AGAIN;
	}
	
	/**
	 * @brief 解析http response truncked body
	 * @return string
	 */
	protected function _unPackChunkBody() {
		while (true) {
			if ( $this->_trunkLength === 0 ) {
				$lenPart = strstr($this->_buffer, "\r\n", true);
				if ( false === $lenPart ) {
					return AHA_AGAIN;
				}
				$trunkLength = hexdec($lenPart);
				if ( $trunkLength === 0 ) {
					return AHA_DECLINED;
				}
				$this->_trunkLength = $trunkLength;
				$this->_buffer = substr($this->_buffer, strlen($lenPart) + 2 );
			} else {
				if (strlen($this->_buffer) < $this->_trunkLength ) {
					return AHA_AGAIN;
				}
				$this->_responseBody .= substr($this->_buffer, 0, $this->_trunkLength);
				$this->_buffer		= substr($this->_buffer, $this->_trunkLength + 2);
				$this->_trunkLength	= 0;
			}
		}
		return AHA_AGAIN;
	}

	/**
	 * @brief 解析http response
	 * @param \swoole_client $client
	 * @param type $data
	 * @return string
	 */
	protected function _unPackResponse(\swoole_client $client, $data) {
		$this->_buffer .= $data;
		if ( empty($this->_responseHeader) && strrpos($this->_buffer, "\r\n\r\n") ) {
			$this->_unPackResponseHeader();
		}
		
		if ( empty($this->_responseHeader) ) {
			return AHA_AGAIN;
		}
		
		if ( AHA_AGAIN === $this->_unPackResponseBody() ) {
			return AHA_AGAIN;
		}
		
		if ( null !== $this->_timer ) {
			//\Aha\Network\Timer::del($this->_timer);
		}
		
		if ( isset($this->_responseHeader['content-encoding']) ) {
			$this->_responseBody = \Aha\Client\Http::gzDecode($this->_responseBody, 
										$this->_responseHeader['content-encoding']);
		}
		
		$response = array(
			'errno'		=> \Aha\Network\Client::ERR_SUCESS, 
			'errmsg'	=> 'sucess',
			'requestId'	=> $this->_requestId,
			'const'		=> microtime(true) - $this->_const,
			'data'		=> array(
				'header'	=> $this->_responseHeader,
				'body'		=> $this->_responseBody
			)
		);
		
		try {
			call_user_func($this->_callback, $response);
		} catch (\Exception $ex) {
			\Aha\Log\Sys::log()->error("HttpClient callback failed![exception]" . $ex->getMessage());
		}
		
		$this->_connectionManager();
	}
	
	/**
	 * @brief http client connection manager
	 */
	protected function _connectionManager() {
		if ( !isset($this->_responseHeader['connection']) ||
				$this->_responseHeader['connection'] != 'keep-alive' ) {
			$this->_objClient->close();
			$this->_objClient = null;
			return;
		}
		
		//把对应的域名和端口对应的http长连接对象放入对象连接池
		$key = $this->_host . ':' . $this->_port;
		$this->_free();
		\Aha\Client\Pool::freeHttp($key, $this);
	}

	/**
	 * @brief 接收响应数据的callback
	 * @param \swoole_client $client
	 * @param type $data
	 */
	public function onReceive(\swoole_client $client, $data) {
		$this->_unPackResponse($client, $data);
	}
	
	/**
	 * @brief 连接关闭的时候吧雷放回连接回收池
	 * @param \swoole_client $client
	 */
	public function onClose(\swoole_client $client) {
		parent::onClose($client);
		$poolName = $this->_host.':'.$this->_port;
		\Aha\Client\Pool::gcHttp($poolName, $this);
	}
	
	/**
	 * @brief 发生错误的时候 吧连接放回gc 出发close事件可能会gc重复 但是无所谓
	 * @param \swoole_client $client
	 */
	public function onError(\swoole_client $client) {
		parent::onError($client);
		$poolName = $this->_host.':'.$this->_port;
		\Aha\Client\Pool::gcHttp($poolName, $this);
	}

	/**
	 * @brief 对外请求开始loop
	 */
	public function loop( $callback = null ) {
		if ( null !== $callback ) {
			$this->setCallback($callback);
		}
		$this->_buildRequest();
		if ( $this->_objClient->sock &&  $this->_objClient->isConnected() ) {
			$this->_send($this->_objClient);//连接池取出的连接 send失败就关闭了吧
			return parent::loop();
		}
		if ( null !== $this->_ip ) {
			$this->_objClient->connect($this->_ip, $this->_port, $this->_connectTimeout);
			return parent::loop();
		}
		//异步查找ip
		swoole_async_dns_lookup($this->_host, function($host,$ip){
			$this->_ip = $ip;
			$this->_objClient->connect($this->_ip, $this->_port, $this->_connectTimeout);
			return parent::loop();
		});
	}
	
	/**
	 * @brief 资源释放
	 */
	protected function _free() {
		$this->_url		= null;//url
	
		$this->_requestHeaders	= array();//请求头
		$this->_query			= array();//Query String 请求get参数
		$this->_requestBody		= array();//Post Body 请求体

		//$this->_host	= null;//host
		$this->_path = '/';

		$this->_responseHeader	= array();//响应头
		$this->_responseBody	= null;//响应body

		$this->_buffer		= '';//响应buffer
		$this->_trunkLength	= 0;//transfer-encoding:chunked时
		//$this->_objClient	= null;

		$this->_requestId	= null;
		$this->_package	= null;
		$this->_timer = null;
		
		$this->_callback = null;//重要 释放了callback以后才能释放MVC
	}


	/**
	 * @brief 解压响应数据包
	 * @param type $data
	 * @param  $type
	 * @return type
	 */
	public static function gzDecode($data,  $type = 'gzip') {
        if ($type == 'gzip') {
            return gzdecode($data);
        } elseif ($type == 'deflate') {
            return gzinflate($data);
        } elseif ($type == 'compress') {
            return gzinflate(substr($data, 2, -4));
        } else {
            return $data;
        }
    }

	public function __destruct() {
		
	}
}
