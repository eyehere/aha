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
namespace Aha\Network;

class Timer {

	//定时器事件
	protected static $_arrEvents = array();
	
	/**
	 * @brief 时间循环
	 */
	public static function loop() {
		if ( empty(self::$_arrEvents) ) {
			return AHA_AGAIN;
		}
		
		$loopConst = microtime(true);
		$eventCnt  = count(self::$_arrEvents);
		
		foreach (self::$_arrEvents as $eventId=>$event) {
			$timeout = $event['params']['timeout'];
			$const	 = microtime(true) - $event['params']['const'];
			if ( $const > $timeout ) {
				self::del($eventId);
				$client = $event['client'];
				$event['params']['const'] = $const;
				if ( $client->isConnected() ) {
					$client->close();
					call_user_func($event['callback'], $event['params']);
				}
			}
		}
		$loopConst = sprintf('%2.9f', microtime(true) - $loopConst);
		echo '[' . date('Y-m-d H:i:s') . "] Timer loop [events]$eventCnt [const]$loopConst" . PHP_EOL;
		return AHA_AGAIN;
	}
	
	/**
	 * @brief 添加一个事件到定时器中
	 * @param type $callback
	 * @param type $params
	 * @param type $client
	 * @return type
	 */
	public static function add($callback, $params, $client) {
		return array_push(self::$_arrEvents, compact('callback','params','client')) - 1;
	}
	
	/**
	 * @brief 从定时器中删除事件
	 * @param \int $eventId
	 * @return boolean
	 */
	public static function del(\int $eventId) {
		if ( isset(self::$_arrEvents[$eventId]) ) {
			unset(self::$_arrEvents[$eventId]);
		}
		return true;
	}
	
}