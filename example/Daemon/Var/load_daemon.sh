#! /bin/sh

php_BIN=/home/luweijun/opt/soft/php5/bin/php  #根据环境修改

workDir=`pwd`
scriptDir=`dirname $0`

daemon_BIN=$workDir/$scriptDir"/../Process/Master.php"
master_PID=$workDir/$scriptDir'/Daemon.pid'
daemon_LOG=$workDir/$scriptDir'/../Logs/Daemon.log'

ps -ef | grep -v 'DTC' | grep 'DAEMON_MASTER' | grep -v grep | awk '{print $2}' > $master_PID

datetime=`date +'%Y-%m-%d %k:%M:%S'`

case "$1" in
	start)
		echo "["$datetime"] Starting ... \n" >> $daemon_LOG
		nohup $php_BIN $daemon_BIN >> $daemon_LOG &
		if [ "$?" != 0 ] ; then
			echo "["$datetime"] start failed ...\n" >> $daemon_LOG
			exit 1
		fi
        echo "["$datetime"] start done \n" >> $daemon_LOG
	;;

	stop)
		echo "["$datetime"]Gracefully shutting down ... \n" >> $daemon_LOG
		if [ ! -r $master_PID ] ; then
			echo "["$datetime"]warning, no pid file found - master is not running ?\n" >> $daemon_LOG
			exit 1
		fi
		kill -USR1  `cat $master_PID`
		#kill -KILL `cat $master_PID`	
        echo "["$datetime"] done\n" >> $daemon_LOG
	;;

	restart)
		sh $0 stop
                sleep 1
		sh $0 start
	;;

	*)
		echo "Usage: $0 {start|stop|restart}"
		exit 1
	;;
esac