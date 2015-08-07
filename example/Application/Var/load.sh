#! /bin/sh

server_BIN=../Server/HttpServer.php
master_PID=../Var/httpServer-Master.pid
manager_PID=../Var/httpServer-Manager.pid

case "$1" in
	start)
		echo -n "Starting ... "

		$server_BIN

		if [ "$?" != 0 ] ; then
			echo " failed ..."
			exit 1
		fi
		
                echo " done"
	;;

	stop)
		echo -n "Gracefully shutting down ... "

		if [ ! -r $master_PID ] ; then
			echo "warning, no pid file found - master is not running ?"
			exit 1
		fi

		kill -QUIT `cat $master_PID`
			
                echo " done"
	;;

	quit)
		echo -n "Terminating ... "

		if [ ! -r $server_PID ] ; then
			echo "warning, no pid file found - master is not running ?"
			exit 1
		fi

		kill -TERM `cat $master_PID`

		echo " done"
	;;

	restart)
		$0 stop
		$0 start
	;;

	reload)

		echo -n "Reload service ... "

		if [ ! -r $manager_PID ] ; then
			echo "warning, no pid file found - manager is not running ?"
			exit 1
		fi

		kill -USR1 `cat $manager_PID`

		echo " done"
	;;

	*)
		echo "Usage: $0 {start|stop|quit|restart|reload}"
		exit 1
	;;

esac
