#!/usr/bin/env php
<?php
    
    echo "exec NSBundle mainBundle;";
    require_once(__DIR__ . '/Packages/Framework/Classes/QoqRuntime.php');
    $runtime = new \Qoq\QoqRuntime();
    $runtime->run();
?>