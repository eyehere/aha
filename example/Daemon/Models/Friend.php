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

use \Daemon\Util\Log;

class Friend implements \Iterator {
	
    protected $_cityDir = null;//城市文件目录
    protected $_arrCity = null;//城市集合
    protected $_taskInfo = null;//任务信息
    
    protected $_cityKey = 0;
    protected $_line    = false;
    protected $_lineNo  = 0;
    protected $_fileHandle = null;

    //初始化按城市取数据的迭代器
	public function __construct($cityDir, $arrCity, $taskInfo) {
        if ( in_array(0,$arrCity) ) {
            $arrCity = array(0);
        }
		$this->_cityDir = $cityDir;
        $this->_arrCity = $arrCity;
        $this->_taskInfo = $taskInfo;
        
        $this->_cityKey = 0;
        $this->_line    = false;
        $this->_lineNo  = 0;
        $this->_fileHandle = null;
		
		$this->rewind();
	}
    
    public function rewind() {
        $this->_lineNo = 0;
        
        foreach ( $this->_arrCity as $key=>$city ) {
            $fileName = $this->_cityDir . $city;
            if ( !file_exists($fileName) || ! $this->_fileHandle = fopen($fileName, 'r') ) {
                Log::appLog()->warning( array('cityFileNotFound'=>$fileName) );
                continue;
            } else {
                $this->_cityKey = $key;
                break;
            }
        }
        
        if ( $this->_fileHandle ) {
            fseek($this->_fileHandle, 0);
            $this->_line = fgets($this->_fileHandle);
        }
    }
 
    public function valid() {
        return false !== $this->_line || count($this->_arrCity) > $this->_cityKey + 1;
    }
 
    public function current() {
		if ( false === $this->_line ) {
            if ( $this->valid() ) {
                yield AHA_AGAIN;
            } else {
                yield false;
            }
		} else {
			$taskInfo = array('users'=>$this->_taskInfo);
			$taskInfo['friend_id'] = trim($this->_line, "\n\r");
			yield $taskInfo;
		}
    }
 
    public function key() {
        return $this->_lineNo;
    }
 
    public function next() {
        if ( false !== $this->_line && is_resource($this->_fileHandle) ) {
            $this->_line = fgets($this->_fileHandle);
            $this->_lineNo++;
        } elseif ( count($this->_arrCity) > $this->_cityKey + 1 ) {
            if ( is_resource($this->_fileHandle) ) {
                fclose($this->_fileHandle);
            }
            while( $this->_cityKey <= count($this->_arrCity) ) {
                $fileName = $this->_cityDir . $this->_arrCity[++$this->_cityKey];
                if ( !file_exists($fileName) || ! $this->_fileHandle = fopen($fileName, 'r') ) {
                    Log::appLog()->warning( array('cityFileNotFound'=>$fileName) );
                    continue;
                } else {
                    break;
                }
            }

            if ( $this->_fileHandle ) {
                fseek($this->_fileHandle, 0);
                $this->_line = fgets($this->_fileHandle);
            }
        }
        yield true;
    }
    
    public function __destruct() {
        ;
    }
	
}