<?php
$pid = exec('./ws_pid_test.py > /dev/null 2>&1 & echo $!');
echo $pid;
?>
