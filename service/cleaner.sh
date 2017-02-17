#!/usr/bin/env bash


# File	      : cleaner.sh
# Description : A daemon plays the role of cleaner for TAFree judge client.
# Creator     : Yu Tzu Wu <abby8050@gmail.com>
# License     : MIT


# Constant
PID_CLEANER_FILE=cleaner.pid
PID_MONITOR_FILE=monitor.pid
LOG_CLEANER_FILE=cleaner.log
LOG_MONITOR_FILE=monitor.log



# Variable
ACTION=$1
PID_CLEANER=""
INTERVAL=1
COMMAND="php ContainerController.php"


# Function
monitor() {
	while true; do
		PSLINE=$(ps $PID_CLEANER | wc -l)
		if [ $PSLINE -eq 1 ]; then
			start_cleaner
			echo "$(date) Restart cleaner"
		fi	
		sleep $INTERVAL
	done
}

start_cleaner() {

	$COMMAND &> $LOG_CLEANER_FILE &
	PID_CLEANER=$!
	echo $PID_CLEANER > $PID_CLEANER_FILE
}

start_monitor() {
	monitor &> $LOG_MONITOR_FILE &
	echo $! > $PID_MONITOR_FILE
}

stop() {
	if [ -e $PID_MONITOR_FILE ]; then
		kill $(cat $PID_MONITOR_FILE) 
		rm $PID_MONITOR_FILE
		echo "Monitor has stoped"		
	fi
	if [ -e $PID_CLEANER_FILE ]; then
		kill $(cat $PID_CLEANER_FILE) 
		rm $PID_CLEANER_FILE
		echo "Cleaner has stoped"		
	fi
}


# Main
case $ACTION in
	start)
		start_cleaner
		start_monitor
		echo "Write log in cleaner.log"
		;;
	stop)
		stop
		;;
	*)
		echo "Usage: $0 {start|stop}" >&2
		exit 1
		;;
esac

exit 0;



