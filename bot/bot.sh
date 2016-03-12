#!/bin/bash
trap 'kill -TERM $pid;wait $pid' TERM
trap 'kill -QUIT $pid;wait $pid' QUIT
trap 'kill -INT $pid;wait $pid' INT

python3 bot.py &
pid=$!
wait $pid
wait $pid
while [ $? = 2 ]; do
	echo "You aren't getting me down this easily, I've been told to restart!"
	python3 bot.py &
	pid=$!
	wait $pid
	wait $pid
done