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
namespace Aha\Process\Protocol;

class Package {
	
	/**
	 * @brief 消息内容
	 * @var string
	 */
	protected $_content = '';
	
	/**
	 * @brief 消息包体的结尾字符
	 * @var string 
	 */
	protected $_packageEof = '\r\n\r\n';

	/**
	 * @brief 消息包体的结束符
	 * @param string $packageEof
	 */
	public function __construct($packageEof = null) {
		$this->_content = '';
		if ( null !== $this->_packageEof ) {
			$this->_packageEof = $packageEof;
		}
	}
	
	/**
	 * @brief 收到协议包的内容进行连接
	 * @param string $content
	 */
	public function append($content) {
		$this->_content .= $content;
	}
	
	/**
	 * @brief 读取管道中的消息包体
	 * @param \swoole_process $process
	 */
	public function readPipe(\swoole_process $process) {
		while ( false !== ($content = $process->read()) ) {
			if ( false === $content ) {
				\Aha\Log\Sys::log()->error(array('IPC_PIPE_READ_ERR'=>$content));
                break;
			} else {
				$this->append($content);
			}
			
			if ( strlen($content) < 8191 ) {
				break;
			}
		}
	}
	
	/**
	 * @brief 把管道收到的协议内容进行处理 拆分成单个包体
	 * @return array
	 */
	public function getPackages() {
		$arrPackage = array();
		while ( false !== ( $part = strstr($this->_content, $this->_packageEof, true) ) ) {
			$arrPackage[] = $part;
			$this->_content = substr($this->_content, strlen($part.$this->_packageEof));
		}
		return $arrPackage;
	}
	
}