#!/usr/bin/env php
<?php
    
    echo "exec NSBundle mainBundle;";
    require_once(__dir__ . '/Classes/QoqRuntime.php');
    $runtime = new \Qoq\QoqRuntime();
    $runtime->run();
?>