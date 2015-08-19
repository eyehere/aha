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
		
		$arrUrl			= parse_url($url);
		$this->_host	= $arrUrl['host'];
		$this->_port	= isset($arrUrl['port']) ? $arrUrl['port'] : 80;
		if ( $arrUrl['scheme'] === 'https' ) {
			$this->_port = 443;
		}
		$this->_path = isset($arrUrl['path']) ? $arrUrl['path'] : '/';
		$client = new \swoole_client(SWOOLE_SOCK_TCP , SWOOLE_SOCK_ASYNC);
		parent::__construct($client, $this->_host, $this->_port, $timeout, $connectTimeout);
		$this->_init();
		$this->_initConfig();
	}
	
	/**
	 * @brief 初始化请求头
	 */
	protected function _init() {
		$this->_requestHeaders = array(
			'Host'				=> $this->_host,
			'User-Agent'		=> 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/34.0.1847.116 Safari/537.36',
			'Accept'			=> 'text/xml,application/xml,application/xhtml+xml,text/html,text/plain,image/png,image/jpeg,image/gif,*/*',
			'Accept-Language'	=> 'zh-CN,zh;q=0.8,en;q=0.6,zh-TW;q=0.4,ja;q=0.2',
			'Accept-Encoding'	=> 'gzip,deflate,sdch'
		);
		
		if ( function_exists('gzdecode') ) {
			$this->_requestHeaders['Accept-Encoding'] = 'gzip,deflate,sdch';
		}

		if ( $this->_method === 'POST' ) {
			$this->_requestHeaders['Content-Type'] = 'application/x-www-form-urlencoded';
		}
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
		call_user_func($this->_callback, $response);
		$client->close();
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
	 * @brief 对外请求开始loop
	 */
	public function loop() {
		$this->_buildRequest();
		parent::loop();
		$this->_objClient->connect($this->_host, $this->_port, $this->_connectTimeout);
	}
	
	/**
	 * @brief 资源释放
	 */
	protected function _free() {
		$this->_url		= null;//url
	
		$this->_requestHeaders	= null;//请求头
		$this->_query			= null;//Query String 请求get参数
		$this->_requestBody		= null;//Post Body 请求体

		$this->_host	= null;//host

		$this->_responseHeader	= null;//响应头
		$this->_responseBody	= null;//响应body

		$this->_buffer		= null;//响应buffer
		$this->_trunkLength	= null;//transfer-encoding:chunked时
		$this->_objClient	= null;

		$this->_requestId	= null;
		$this->_package	= null;
		$this->_timer = null;
		
		$this->_callback = null;//重要 释放了callback以后才能释放MVC
		
		\Aha\Client\Pool::freeHttp($this);//把当前对象
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
		var_dump(__METHOD__);
	}
}
