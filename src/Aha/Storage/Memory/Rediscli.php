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
namespace Aha\Storage\Memory;
use \Aha\Network\Client;

class Rediscli extends Client {
	
	/**
	 * @brief 回调arguments
	 * @var type 
	 */
	protected $_arguments = null;
	
	protected $_buffer = '';
	
    protected $_wait_recv = false;
    protected $_multi_line = false;

	/**
	 * @brief 实例化redis client
	 * @param type $conf
	 * @return \Aha\Storage\Memory\Rediscli
	 */
	public function __construct($conf) {
		$host	= $conf['host'];
		$port	= $conf['port'];
		$timeout= $conf['timeout'];
		$client = new \swoole_client(SWOOLE_SOCK_TCP, SWOOLE_SOCK_ASYNC);
		parent::__construct($client, $host, $port, $timeout);
		$this->_initConfig();
		return $this;
	}
	
	/**
	 * 初始化config 
	 */
	protected function _initConfig() {
		$setting = array(
			'open_tcp_nodelay'		=>  true
		);
		$this->_objClient->set($setting);
	}
	
	/**
	 * @brief 设置要发送的请求数据包
	 * @param type $package
	 * @return \Aha\Storage\Memory\Rediscli
	 */
	public function setPackage($package) {
		$this->_package = $package;
		return $this;
	}
	
	/**
	 * @brief 设置回调参数
	 * @param type $arguments
	 * @return \Aha\Storage\Memory\Rediscli
	 */
	public function setArguments($arguments) {
		$this->_arguments = $arguments;
		return $this;
	}
	
	/**
	 * @brief 连接成功时的回调 发送数据到server
	 * @param \swoole_client $client
	 */
	public function onConnect(\swoole_client $client) {
		//如果没有等待连接发送的数据
		if ( empty($this->_package) ) {
			return;
		}
		//如果有等待连接发送的数据
		$this->_send($client);
	}
	
	/**
	 * @brief 发送数据
	 * @param type $client
	 * @return boolean
	 */
	protected function _send($client) {
		if ( ! $client->send($this->_package) ) {//发送失败的回调和资源回收
			$error = array(
				'errno'		=> \Aha\Network\Client::ERR_SEND_FAILED, 
				'errmsg'	=> array('errCode'=>$client->errCode, 'error'=>  socket_strerror($client->errCode)),
				'package'	=> $this->_package
			);
			echo "Redis onConnect send failed![error]" . serialize($error) . PHP_EOL;
			
			$callback  = $this->_callback;
			$arguments = $this->_arguments;
			$this->_free();
		
			try {
			call_user_func($callback, false, $arguments['callback'],$this);
			} catch (\Exception $ex) {
				echo "Redis onConnect send callback failed![exception]" . $ex->getMessage() . PHP_EOL;
			}
			$this->_free();
			return false;
		}
		/*
		if ( floatval($this->_timeout) > 0 ) {
			$response = array(
				'errno'		=> \Aha\Network\Client::ERR_REQUEST_TIMEOUT, 
				'errmsg'	=> 'request_timeout',
				'requestId'	=> $this->_requestId,
				'const'		=> $this->_const,
				'timeout'	=> $this->_timeout,
				'data'		=> array()
			);
			$this->_timer = \Aha\Network\Timer::add($this->_callback, $response, $this->_objClient);
		}*/
		return true;
	}

	/**
	 * @brief 发生错误时的回调
	 * @param \swoole_client $client
	 */
	public function onError(\swoole_client $client) {
		$error = array(
			'errno'		=> \Aha\Network\Client::ERR_UNEXPECT, 
			'errmsg'	=> array('errCode'=>$client->errCode, 'error'=>  socket_strerror($client->errCode)),
			'package'	=> $this->_package
		);
		echo "Redis onError![error]" . serialize($error) . PHP_EOL;
		
		$callback  = $this->_callback;
		$arguments = $this->_arguments;
		$this->_free();
		
		try {
			call_user_func($callback, false, $arguments['callback'],$this);
		} catch (\Exception $ex) {
			echo "Redis onError callback![exception]" . $ex->getMessage() . PHP_EOL;
		}

		if ( $client->isConnected() ) {
			$client->close();
		}
	}

	/**
	 * @brief receive数据处理
	 * @param \swoole_client $client
	 * @param type $data
	 * @return type
	 */
	public function onReceive(\swoole_client $client, $data) {
        if ($this->_wait_recv) {
            return $this->_waitReceive($data);
        }
		
        $lines = explode("\r\n", $data, 2);
        $type = $lines[0][0];
		
        if ($type == '-') {
            $result = substr($lines[0], 1);
			echo "Rediscli parse error:[data]$data" . PHP_EOL;
			return $this->_notify(false, $result);
        } elseif ($type == '+') {
            $result = substr($lines[0], 1);
			return $this->_notify($result);	
        } elseif ($type == '$') {//只有一行数据
            return $this->_parseLine($data);
        } elseif ($type == '*') {//多行数据
            return $this->_parseMultiLine($data);
        } elseif ($type == ':') {
            $result = intval(substr($lines[0], 1));
            return $this->_notify($result);
        } else {
            $message = "Response is not a redis result. String:$data";
			echo $message . PHP_EOL;
			return $this->_notify(false, $message);
        }
	}
	
	/**
	 * @brief 只有一行数据的解析
	 * @param type $data
	 * @return type
	 */
	protected function _parseLine($data) {
		$lines = explode("\r\n", $data, 2);
		$len = intval(substr($lines[0], 1));
		if ($len > strlen($lines[1])) {
			$this->_wait_recv = $len;
			$this->_buffer = $lines[1];
			$this->_multi_line = false;
			return;
		}
		$result = rtrim($lines[1], "\r\n");
		return $this->_notify($result);
	}
	
	/**
	 * @brief 多行数据模式响应解析
	 * @param type $data
	 * @return type
	 */
	protected function _parseMultiLine($data) {
		
		$dataLines = array();
		$requireLineLen=0;
		$linesCnt =0;
		
		if ( $this->_multi_line ) {
			$dataLines	 = explode("\r\n", $this->_buffer);
			$requireLineLen = $this->_multi_line * 2 - substr_count($this->_buffer, "$-1\r\n");
			$linesCnt = count($dataLines) - 1;
		}
		
		if ( $this->_multi_line && $linesCnt == $requireLineLen) {
			$result = array();
			$index = 0;
			for ($i = 0; $i < $linesCnt; $i++) {
				if (substr($dataLines[$i], 1, 2) === '-1') {//not exists
					$value = false;
				} else {
					$value = $dataLines[$i + 1];
					$i++;
				}
				if ($this->_arguments['fields']) {
					$result[$this->_arguments['fields'][$index]] = $value;
				} else {
					$result[] = $value;
				}
				$index++;
			}
			return $this->_notify($result);
		}
		
		//数据不足，需要继续等待
		$lines = explode("\r\n", $data, 2);
		$dataLineNum = intval(substr($lines[0], 1));
		$this->_multi_line = $dataLineNum;
		$this->_buffer = $lines[1];
		$this->_wait_recv = true;
		return;
	}

	/**
	 * @brief 数据等待
	 * @param type $data
	 * @return type
	 */
	protected function _waitReceive($data) {
		$this->_buffer .= $data;
		if ($this->_multi_line) {
			$requireLineLen = $this->_multi_line * 2 + 1 - substr_count($this->_buffer, "$-1\r\n");
			if (substr_count($this->buffer, "\r\n") - 1 == $requireLineLen) {
				return $this->_parseMultiLine($data);
			}
		} else {
			if (strlen($this->_buffer) >= $this->_wait_recv) {
				$result = rtrim($this->buffer, "\r\n");
				return $this->_notify($result);
			}
		}
		return;
	}

	/**
	 * @brief 通知上层redis指令的执行结果
	 * @param type $result
	 */
	protected function _notify($result, $error=null) {
		$callback  = $this->_callback;
		$arguments = $this->_arguments;
		$this->_free();
		
		try {
			call_user_func($callback, $result, $arguments['callback'],$this, $error);
		} catch (\Exception $ex) {
			echo "Redis onReceive notify callback![exception]" . $ex->getMessage() . PHP_EOL;
		}
	}

	/**
	 * @brief 请求驱动
	 * @return \Aha\Network\Client
	 */
	public function loop() {
		$this->_const = microtime(true);
		if ( ! $this->_objClient->isConnected() ) {
			$this->_objClient->connect($this->_host, $this->_port, $this->_connectTimeout);
		} else {
			$this->_send($this->_objClient);
		}
		
		return $this;
	}
	
	/**
	 * @brief 对象资源释放
	 */
	protected function _free() {
		$this->_arguments	= null;
		$this->_buffer = '';
		$this->_wait_recv = false;
		$this->_multi_line = false;
		
		$this->_timer		= null;
		$this->_const		= 0;
		$this->_callback	= null;
		$this->_package		= null;
		$this->_requestId	= null;
	}

}