#! /bin/sh

workDir=`pwd`
scriptDir=`dirname $0`

daemonShell=$scriptDir'/load_daemon.sh'

reloadFile=$workDir/$scriptDir'/reload_daemon'
reload=`cat $reloadFile`

workerNum=`ps -ef | grep 'DAEMON' | grep -v 'grep' | wc -l`
masterNum=`ps -ef | grep 'DAEMON_MASTER' | grep -v 'grep' | wc -l`
driveNum=`ps -ef | grep 'DAEMON_DRIVE' | grep -v 'grep' | wc -l`
workerNum=`ps -ef | grep 'DAEMON_WORKER' | grep -v 'grep' | wc -l`
redoNum=`ps -ef | grep 'DAEMON_REDO' | grep -v 'grep' | wc -l`
statsNum=`ps -ef | grep 'DAEMON_STATS' | grep -v 'grep' | wc -l`


if [ $reload -eq 1 ] || [ $masterNum -eq 0 ] || [ $driveNum -eq 0 ] || [ $redoNum -eq 0 ] || [ $statsNum -eq 0 ] || [ $workerNum -lt 20 ];then
    
    ps -ef | grep 'DAEMON_MASTER' | grep -v 'grep' | awk '{print $2}' | while read line;do kill -9 $line;done
    ps -ef | grep 'DAEMON' | grep -v 'grep'| awk '{print $2}' | while read line;do kill -9 $line;done
    
    sh $daemonShell start
    echo 0 > $reloadFile
fi