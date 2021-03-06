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

class Coroutine {
	
	/**
	 * @breif mysqli对象
	 * @var type 
	 */
	protected $_mysqli = null;

	/**
	 * @bief sql
	 * @var type 
	 */
	protected $_sql = null;

	/**
	 * @bief 开启一个协程
	 * @param \Aha\Storage\Db\Mysqli $mysqli
	 * @return \Aha\Storage\Db\Coroutine
	 */
	public function __construct( \Aha\Storage\Db\Mysqli $mysqli ) {
		$this->_mysqli = $mysqli;
		return $this;
	}
	
	/**
	 * @brief 执行query
	 * @param type $sql
	 * @return \Aha\Storage\Db\Coroutine
	 */
	public function query($sql) {
		$this->_sql = $sql;
		return $this;
	}
	
	/**
	 * @brief 协程执行
	 * @param type $callback
	 */
	public function execute($callback) {
		$this->_mysqli->query($this->_sql, $callback);
	} 
	
}