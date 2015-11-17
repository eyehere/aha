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

namespace Daemon\Models;

class Demo {
    
    protected $_objAha = null;

    public function __construct($objAha) {
        $this->_objAha = $objAha;
    }

    public function getFromDb() {
		$config = $this->_objAha->getConfig();
		$dbName = 'test';
		$dbConf = $config->get('database', $dbName);
		$conn = \Aha\Storage\Db\Pool::getConnection($dbName, $dbConf);
		$ret = ( yield $conn->createCoroutine()
			 ->query("select * from user where locker=0 order by id desc limit 20") );
		if ( !isset($ret['result']) || false === $ret['result'] ) {
            yield false;
        } else {
            $arrData = $ret['result']->fetch_all(MYSQLI_ASSOC);
            yield $arrData;
        }
	}
	
	public function dbTrans() {
		$config = $this->_objAha->getConfig();
		$dbName = 'test';
		$dbConf = $config->get('database', $dbName);
		$conn = \Aha\Storage\Db\Pool::getConnection($dbName, $dbConf);
		$trans = $conn->beginTrans()
				->queue('user','insert into user set name="Aha",phone="15801228065"')
                ->queue('user','insert into user set name="eyehere",phone="15801228065"')
				->queue('friends',function($result){
					$friendId = intval($result['user']['last_insert_id']);
					$sql = 'insert into friends set user_id=6,friend_id='.$friendId;
					return $sql;
				});
		$ret = ( yield $trans );
        if ( !isset($ret['result']) || false === $ret['result'] ) {
            yield false;
        } else {
            yield true;
        }
	}
    
    public function lockTask($arrTask, $workerPid) {
		$config = $this->_objAha->getConfig();
		$dbName = 'test';
		$dbConf = $config->get('database', $dbName);
		$conn = \Aha\Storage\Db\Pool::getConnection($dbName, $dbConf);
        
        $arrId = array_column($arrTask,'id');
        $strId = implode(',', $arrId);
        
		$ret = ( yield $conn->createCoroutine()
			 ->query("update user set locker=$workerPid where id in (".$strId.") and locker=0") );
		if ( !isset($ret['result']) || false === $ret['result'] ) {
            yield false;
        } else {
            yield true;
        }
	}
    
    public function doTask($content) {
        $package = $content['content'];
		$config = $this->_objAha->getConfig();
		$dbName = 'test';
		$dbConf = $config->get('database', $dbName);
		$conn = \Aha\Storage\Db\Pool::getConnection($dbName, $dbConf);
		$trans = $conn->beginTrans();
        $friendId = $package['friend_id'];
        foreach ( $package['users'] as $user ) {
            $trans->queue($user['id'],"insert into friends set user_id=".$user['id'].",friend_id=$friendId");
        }
		$ret = ( yield $trans );
        if ( !isset($ret['result']) || false === $ret['result'] ) {
            yield false;
        } else {
            yield true;
        }
	}
    
}
/*
 CREATE TABLE `user` (
	`id` INT(10) NOT NULL AUTO_INCREMENT,
	`name` VARCHAR(32) NULL DEFAULT '',
	`phone` CHAR(11) NULL DEFAULT '',
	`locker` INT(10) NULL DEFAULT '0',
	PRIMARY KEY (`id`)
)
COLLATE='utf8_general_ci'
ENGINE=InnoDB;

CREATE TABLE `friends` (
	`id` INT(10) NOT NULL AUTO_INCREMENT,
	`user_id` INT(10) NULL DEFAULT '0',
	`friend_id` INT(10) NOT NULL DEFAULT '0',
	PRIMARY KEY (`id`)
)
COLLATE='utf8_general_ci'
ENGINE=InnoDB;

 */