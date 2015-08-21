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
namespace Aha\Storage\Db;

class Pool {
	
	/**
	 * @brief 数据库连接池管理，方便获取数据库连接
	 * @var type 
	 */
	protected static $_dbPool	= array();
	
	/**
	 * @brief 获取数据库连接 方便管理
	 * @param type $instanceName
	 * @param type $dbConf
	 * @return type
	 */
	public static function getConnection($instanceName, $dbConf = array()) {
		if ( !isset(self::$_dbPool[$instanceName]) ) {
			self::$_dbPool[$instanceName] = new \Aha\Storage\Db\Mysqli($dbConf);
		}
		return self::$_dbPool[$instanceName];
	}
	
}