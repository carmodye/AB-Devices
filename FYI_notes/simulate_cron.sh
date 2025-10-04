#!/bin/bash

while true; do
    echo "Running Laravel scheduler at $(date)"
    ./vendor/bin/sail artisan schedule:run 
    #>> /tmp/scheduler-loop.log 2>&1
    sleep 60
done