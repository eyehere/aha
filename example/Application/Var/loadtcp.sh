#! /bin/sh

php_BIN=/home/luweijun/opt/soft/php/bin/php
server_BIN=../Server/TcpServer.php
master_PID=../Var/Master.pid
manager_PID=../Var/Manager.pid

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
