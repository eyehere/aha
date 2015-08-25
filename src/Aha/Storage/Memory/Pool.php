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

class Pool {
	
	/**
	 * @brief redis连接池管理，方便获取redis连接
	 * @var type 
	 */
	protected static $_redisPool	= array();
	
	/**
	 * @brief redis client gc
	 * @var type 
	 */
	protected static $_gcPool = array();
	
	/**
	 * @brief 获取rdis连接 方便管理
	 * @param type $instanceName
	 * @param type $redisConf
	 * @return type
	 */
	public static function getConnection($instanceName, $conf = array()) {
		if ( !isset(self::$_redisPool[$instanceName]) ) {
			self::$_redisPool[$instanceName] = new \Aha\Storage\Memory\Redis($conf);
		}
		return self::$_redisPool[$instanceName];
	}
	
	/**
	 * @brief redis client gc
	 * @param \Aha\Storage\Memory\Rediscli $redisCli
	 */
	public static function redisCliGc(\Aha\Storage\Memory\Rediscli $redisCli) {
		self::$_gcPool[] = $redisCli;
	}
	
}