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

class Monitor {
	
	const KEY	=	'MOTITOR';
	
	const IPC_PIPE_READ_ERR	= 'IPC_PIPE_READ_ERR';
	const IPC_PIPE_WRITE_ERR= 'IPC_PIPE_WRITE_ERR';
	
	const TABLE_INCR_ERR	= 'TABLE_INCR_ERR';
	const TABLE_DECR_ERR	= 'TABLE_DECR_ERR';
	
	const CONNECT_DB_ERR	= 'CONNECT_DB_ERR';
	const QUERY_DB_ERR		= 'QUERY_DB_ERR';
	
	const CALL_HTTP_API_ERR = 'CALL_HTTP_API_ERR';
	
	const UNEXPECTED_ERR	= 'UNEXPECTED_ERR';
    const UNEXPECTED_PACK	= 'UNEXPECTED_PACK';
	
    const OVER_MAX_RETRY_TIMES = 'OVER_MAX_RETRY_TIMES';
    
    const FLOW_OVER_PREDICT_RESET = 'FLOW_OVER_PREDICT_RESET';
}