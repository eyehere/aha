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
namespace Daemon\Util;

class Constant {
	
	//Master进程read的消息类型
	const PACKAGE_TYPE_TASK			= -1;//消息包类型 发布任务
	const PACKAGE_TYPE_REDO			= -2;//消息包类型 重试任务
	const PACKAGE_TYPE_COMPLETE		= -3;//消息包体类型 发布任务完成
	
	const PACKAGE_EOF	= "\r\n\r\n";//package的结束符
	
}