#!/bin/bash
function checkprocess(){
    if (ps aux|grep -v grep|grep "$2" )
    then
        echo "active"
    else
        echo "miss"
        $1 $2 &
    fi
}
checkprocess $1 $2"/app/ectools/run_in_crond/order_status_cycle.php"
