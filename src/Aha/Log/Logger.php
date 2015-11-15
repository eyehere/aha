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
namespace Aha\Log;

class Logger {

	/**
	 * @brief 多个log的instance
	 * @var array 
	 */
	private static $arrLoggers = array();
	
	//日志级别
	const DEBUG		=	0X00;
	const INFO		=	0X02;
	const NOTICE	=	0X04;
	const WARNING	=	0X08;
	const ERROR		=	0X10;
	const CRITICAL	=	0X20;
	const ALERT		=	0X40;
	const EMERGENCY	=	0X80;

	//日志级别中文对应
	protected static $arrLevels = array(
		0X00	=>	'DEBUG',
		0X02	=>	'INFO',
		0X04	=>	'NOTICE',
		0X08	=>	'WARNING',
		0X10	=>	'ERROR',
		0X20	=>	'CRITICAL',
		0X40	=>	'ALERT',
		0X80	=>	'EMERGENCY'
	);

	//开启web跟踪
	const WEB_TRACE_ON	=	true;
	//开启debug跟踪
	const BACK_TRACE_ON	=	true;
	
	//日志的标识
	private $_strName	=	null;
	
	//日志文件
	private $_strLogFile	=	null;
	
	//日志级别
	private $_logLevel	=	null;

	//记录web相关的信息
	private $_bolWebTrace	=	false;
	
	//记录debug信息
	private $_bolBackTrace	=	false;

	//public log的app应用名称
	private $_appName	=	null;
	
	/**
	 * @brief 日志实例化
	 * @param string $name 标识
	 * @param string $logFile 日志全路径文件
	 */
	public function __construct($name, $logFile, $logLevel=\Aha\Log\Logger::DEBUG,	$webTrace = false,	$backTrace = false) {
		$this->_strName		=	$name;
		if ( false === strpos($logFile, 'php://') ) {
			$logPath = dirname($logFile);
			if ( !is_dir($logPath) ) {
				mkdir($logPath, 0755, true);
			}
		}
		$this->_strLogFile	= $logFile;
		$this->_logLevel	= $logLevel;
		$this->_bolWebTrace	= $webTrace;
		$this->_bolBackTrace= $backTrace;
	}
    
    /**
     * @brief 获取日志文件
     * @return type
     */
    public function getLogFileName() {
        return $this->_strLogFile;
    }
    
    /**
     * @brief 设置日志文件
     * @param type $logFileName
     */
    public function setLogFileName($logFileName) {
        $this->_strLogFile = $logFileName;
    }
	
	/**
	 * @brief 日志格式化
	 * @param array $arrLog
	 * @return string
	 */
	protected function _lineFormat($arrLog) {
		$line = '';
		foreach ($arrLog as $mark=>$log) {
			
			$line .= "[$mark]";
			if ( is_array($log) ) {
				foreach ( $log as $key=>$val ) {
					if ( !is_scalar($val) ) {
						$val = serialize($val);
					}
					$line .= strval($key) . '=' . strval($val) . ' ';
				}
			} elseif (is_string($log) ) {
				$line .= $log;
			} else {
				$line .= serialize($log);
			}
			
		}
		return $line . PHP_EOL;
	}
	
	/**
	 * @brief 写日志
	 * @param int $level
	 * @param array $arrLog
	 * @return boolean
	 */
	protected function _writeLog($level, $arrLog) {
		if ( $level < $this->_logLevel ) {
			return true;
		}
		
		$arrPrepend = array(
			'write_time'	=>	date('Y-m-d H:i:s')
		);
		
		if ( !is_array($arrLog[\Aha\Log\Logger::$arrLevels[$level]]) ) {
			$arrLog[\Aha\Log\Logger::$arrLevels[$level]] = array($arrLog[\Aha\Log\Logger::$arrLevels[$level]]);
		}
		
		$arrLog[\Aha\Log\Logger::$arrLevels[$level]] = array_merge($arrPrepend, $arrLog[\Aha\Log\Logger::$arrLevels[$level]]);
		
		if ( $this->_bolWebTrace ) {
			$arrLog['WEB_TRACE'] = $this->_getWebTrace();
		}
		
		if ( $this->_bolBackTrace ) {
			$arrLog['BACK_TRACE'] = $this->_getBackTrace();
		}
		
		$logContent = $this->_lineFormat($arrLog);
		return $this->_write($logContent);
	}
	
	/**
	 * @brief web访问信息跟踪
	 * @return array
	 */
	protected function _getWebTrace() {
		return array(
			'uri'	=>	isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '',
			'query'	=>	isset($_SERVER['QUERY_STRING']) ? $_SERVER['QUERY_STRING'] : '',
			'refer'	=>	isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '',
			'ip'	=>	isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '',
		);
	}
	
	/**
	 * @brief debug 跟踪信息
	 * @return array
	 */
	protected function _getBackTrace() {
		$trace = debug_backtrace();
		array_shift($trace);
		array_shift($trace);
		return array(
			'file'		=>	isset($trace[0]) && isset($trace[0]['file']) ? $trace[0]['file'] : null,
			'line'		=>	isset($trace[0]) && isset($trace[0]['line']) ? $trace[0]['line'] : null,
			'class'		=>	isset($trace[1]) && isset($trace[1]['class']) ? $trace[1]['class'] : null,
			'function'	=>	isset($trace[1]) && isset($trace[1]['function']) ? $trace[1]['function'] : null,
		);
	}

	/**
	 * @brief debug log
	 * @param array $arrLog
	 * @return boolean
	 */
	public function debug($arrLog) {
		$arrMessage = array(
			\Aha\Log\Logger::$arrLevels[\Aha\Log\Logger::DEBUG]	=> $arrLog
		);
		return $this->_writeLog(\Aha\Log\Logger::DEBUG, $arrMessage);
	}
	
	/**
	 * @brief info log
	 * @param array $arrLog
	 * @return boolean
	 */
	public function info($arrLog) {
		$arrMessage = array(
			\Aha\Log\Logger::$arrLevels[\Aha\Log\Logger::INFO]	=> $arrLog
		);
		return $this->_writeLog(\Aha\Log\Logger::INFO, $arrMessage);
	}
	
	/**
	 * @brief notice log
	 * @param array $arrLog
	 * @return boolean
	 */
	public function notice($arrLog) {
		$arrMessage = array(
			\Aha\Log\Logger::$arrLevels[\Aha\Log\Logger::NOTICE]	=> $arrLog
		);
		return $this->_writeLog(\Aha\Log\Logger::NOTICE, $arrMessage);
	}
	
	/**
	 * @brief warning log
	 * @param array $arrLog
	 * @return boolean
	 */
	public function warning($arrLog) {
		$arrMessage = array(
			\Aha\Log\Logger::$arrLevels[\Aha\Log\Logger::WARNING]	=> $arrLog
		);
		return $this->_writeLog(\Aha\Log\Logger::WARNING, $arrMessage);
	}
	
	/**
	 * @brief error log
	 * @param array $arrLog
	 * @return boolean
	 */
	public function error($arrLog) {
		$arrMessage = array(
			\Aha\Log\Logger::$arrLevels[\Aha\Log\Logger::ERROR]	=> $arrLog
		);
		return $this->_writeLog(\Aha\Log\Logger::ERROR, $arrMessage);
	}
	
	/**
	 * @brief critical log
	 * @param array $arrLog
	 * @return boolean
	 */
	public function critical($arrLog) {
		$arrMessage = array(
			\Aha\Log\Logger::$arrLevels[\Aha\Log\Logger::CRITICAL]	=> $arrLog
		);
		return $this->_writeLog(\Aha\Log\Logger::CRITICAL, $arrMessage);
	}
	
	/**
	 * @brief alert log
	 * @param array $arrLog
	 * @return boolean
	 */
	public function alert($arrLog) {
		$arrMessage = array(
			\Aha\Log\Logger::$arrLevels[\Aha\Log\Logger::ALERT]	=> $arrLog
		);
		return $this->_writeLog(\Aha\Log\Logger::ALERT, $arrMessage);
	}
	
	/**
	 * @brief emergency log
	 * @param array $arrLog
	 * @return boolean
	 */
	public function emergency($arrLog) {
		$arrMessage = array(
			\Aha\Log\Logger::$arrLevels[\Aha\Log\Logger::EMERGENCY]	=> $arrLog
		);
		return $this->_writeLog(\Aha\Log\Logger::EMERGENCY, $arrMessage);
	}
	
	/**
	 * @brief public日志行格式化
	 * @param array $arrLog
	 * @return string
	 */
	protected function _publicLineFormat($arrLog) {
		$line = 'app=' . strval($this->_appName) . '||';
		if ( is_array($arrLog) ) {
			foreach ( $arrLog as $key=>$val ) {
				if ( !is_scalar($val) ) {
					$val = serialize($val);
				}
				$line .= strval($key) . '=' . strval($val) . '||';
			}
		} elseif (is_string($arrLog) ) {
			$line .= $arrLog;
		} else {
			$line .= serialize($arrLog);
		}
		$line .= 'write_time=' . date('Y-m-d H:i:s') . PHP_EOL;
		return $line;
	}
	
	/**
	 * @brief 写public日志
	 * @param string $arrLog
	 * @return boolean
	 */
	public function pub($arrLog) {
		$logContent = $this->_publicLineFormat($arrLog);
		return $this->_write($logContent);
	}
	
	/**
	 * @brief public log设置应用名称
	 * @param string $appName
	 * @return boolean
	 */
	public function setAppName($appName) {
		$this->_appName = $appName;
		return true;
	}

	/**
	 * @brief 写日志
	 * @param string $logContent
	 * @return boolean
	 * @throws Exception
	 */
	protected function _write($logContent) {
		//file_put_contents()
		//if ( false === swoole_async_write($this->_strLogFile, $logContent, -1) ) {
			//throw new \Exception("log file {$this->_strLogFile} write failed!");
        if ( false === file_put_contents($this->_strLogFile, $logContent, FILE_APPEND) ) {
            echo "log file {$this->_strLogFile} write failed!".PHP_EOL;
		}
		return true;
	}


	/**
	 * @brief 获取log实例
	 * @param type $name
	 * @param type $arguments
	 */
	public static function getLoggerInstance($name, $logFile, $logLevel, $webTrace, $backTrace) {
		if ( !isset(\Aha\Log\Logger::$arrLoggers[$name]) ) {
			\Aha\Log\Logger::$arrLoggers[$name] = new \Aha\Log\Logger($name, $logFile, $logLevel, $webTrace, $backTrace);
		}
        $objLogger = \Aha\Log\Logger::$arrLoggers[$name];
		
		if ( false === strpos($logFile, 'php://') && $objLogger->getLogFilename() != $logFile ) {
			$logPath = dirname($logFile);
			if ( !is_dir($logPath) ) {
				mkdir($logPath, 0755, true);
			}
			$objLogger->setLogFileName($logFile);
		}
		
		return $objLogger;
	}

	/**
	 * @brief 静态魔术方法获取log实例
	 * @param string $name
	 * @param array $arguments
	 * @return obj
	 */
	public static function __callStatic($name, $arguments) {
		$logFile	= isset($arguments[0]) && is_string($arguments[0]) ? $arguments[0] : false;
		if ( false === $logFile ) {
			throw new \Exception("Log {$name} file name is not allowed null");
		}
		$logLevel	= isset($arguments[1]) ? $arguments[1] : \Aha\Log\Logger::DEBUG;
		$webTrace	= isset($arguments[2]) ? $arguments[2] : false;
		$backTrace	= isset($arguments[3]) ? $arguments[3] : false;
		return \Aha\Log\Logger::getLoggerInstance($name, $logFile, $logLevel, $webTrace, $backTrace);
	}
	
}

