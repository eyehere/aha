#! /bin/sh

php_BIN=/home/luweijun/opt/soft/php/bin/php

workDir=`pwd`
scriptDir=`dirname $0`

server_BIN=$workDir/$scriptDir"/../Server/HttpServer.php"
master_PID=$workDir/$scriptDir"/../Var/Master.pid"
manager_PID=$workDir/$scriptDir"/../Var/Manager.pid"

case "$1" in
	start)
		echo "Starting ... "

		$php_BIN $server_BIN

		if [ "$?" != 0 ] ; then
			echo " failed ..."
			exit 1
		fi
		
        echo " done"
	;;

	stop)
		echo "Gracefully shutting down ... "

		if [ ! -r $master_PID ] ; then
			echo "warning, no pid file found - master is not running ?"
			exit 1
		fi

		kill -TERM `cat $master_PID`
		kill -KILL `cat $master_PID`
			
        echo " done"
	;;

	restart)
		$0 stop
                sleep 1
		$0 start
	;;

	reload)

		echo "Reload service ... "

		if [ ! -r $manager_PID ] ; then
			echo "warning, no pid file found - manager is not running ?"
			exit 1
		fi

		kill -USR1 `cat $manager_PID`

		echo " done"
	;;

	*)
		echo "Usage: $0 {start|stop|restart|reload}"
		exit 1
	;;

esac
