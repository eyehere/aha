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
namespace Application\Util;

use Aha\Log\Logger;

class Log {
	
	//log路径
	const LOG_PATH = '/Logs/';
	
	//public日志路径
	const PUBLIC_LOG_PATH = '/Logs/';
	
	/**
	 * @brief public log
	 * @return obj
	 */
	public static function publicLog() {
		$logFile = dirname(__DIR__) . self::PUBLIC_LOG_PATH . 'public.log';
		$objLogger = Logger::pubLog($logFile);
		$objLogger->setAppName('appname');
		return $objLogger;
	}
	
	/**
	 * @brief 应用日志
	 * @return obj
	 */
	public static function appLog() {
		$logFile = dirname(__DIR__) . self::LOG_PATH .'app-' . date('Ymd') . '.log';
		$objLogger = Logger::appLog($logFile, Logger::DEBUG, 
						Logger::WEB_TRACE_ON, Logger::BACK_TRACE_ON);
		return $objLogger;
	}
	
	/**
	 * @brief 监控日志
	 * @return type
	 */
	public static function monitor() {
		$logFile = dirname(__DIR__) .  self::LOG_PATH . 'monitor.log';
		$objLogger = Logger::monitorLog($logFile,  Logger::ERROR, 
						Logger::WEB_TRACE_ON, Logger::BACK_TRACE_ON);
		return $objLogger;
	}

}
