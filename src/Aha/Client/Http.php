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
	protected $_query	= array();//Query String
	protected $_body	= array();//Post Body
	
	protected $_requestHeaders = array();

	protected $_host	= null;//host
	protected $_port	= 80;//port
	protected $_path	= '/';

	/**
	 * @brief 初始化一个http客户端
	 * @param \string $host
	 * @param \int $port
	 * @param type $timeout
	 * @param type $connectTimeout
	 */
	public function __construct(\string $method,\string $url, $timeout = 1, $connectTimeout = 0.05) {
		if ( !in_array($method, array('GET','POST')) ) {
			throw new Exception("unsupport http method {$method}");
		}
		$this->_method	= $method;
		$this->_url		= $url;
		
		$arrUrl = parse_url($url);
		$this->_host	= $arrUrl['host'];
		$this->_port = isset($arrUrl['port']) ? $arrUrl['port'] : 80;
		if ( $arrUrl['scheme'] === 'https' ) {
			$this->_port = 443;
		}
		$this->_path = isset($arrUrl['path']) ? $arrUrl['path'] : '/';
		
		$client = new \swoole_client(SWOOLE_SOCK_TCP , SWOOLE_SOCK_ASYNC);
		parent::__construct($client, $this->_host, $this->_port, $timeout, $connectTimeout);
		$this->_init();
	}
	
	protected function _init() {
		$this->_requestHeaders = array(
			'Host'			=> $this->_host,
			'User-Agent'	=> 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/34.0.1847.116 Safari/537.36',
			'Accept'		=> 'text/xml,application/xml,application/xhtml+xml,text/html,text/plain,image/png,image/jpeg,image/gif,*/*',
			'Accept-language'=> 'zh-CN,zh;q=0.8,en;q=0.6,zh-TW;q=0.4,ja;q=0.2',
			'Accept-encoding'=> 'gzip,deflate,sdch'
		);
		
		if ( $this->_method === 'POST' ) {
			$this->_requestHeaders['Content_Type'] = 'application/x-www-form-urlencoded';
		}
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
	public function setBody( array $body ) {
		$this->_body = $body;
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
	
	
	protected function _buildRequest() {
		if ( !empty($this->_query) ) {
			$this->_path .= '?' . http_build_query($this->_query);
		}
		$header = "{$this->_method} {$this->_path} HTTP/1.1";
		
		
	}

	/**
	 * @brief 接收响应数据的callback
	 * @param \swoole_client $client
	 * @param type $data
	 */
	public function onReceive(\swoole_client $client, $data) {
		$client->close();
		$response = array(
			'errno'		=> \Aha\Network\Client::ERR_SUCESS, 
			'errmsg'	=> 'sucess',
			'requestId'	=> $this->_requestId,
			'const'		=> microtime(true) - $this->_const,
			'data'		=> $data
		);
		call_user_func($this->_callback, $response);
	}
	
	/**
	 * @brief 对外请求开始loop
	 */
	public function loop() {
		$this->_buildRequest();
		parent::loop();
		$this->_objClient->connect($this->_host, $this->_port, $this->_connectTimeout);
	}

}