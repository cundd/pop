#!/usr/bin/env php
<?php
    
    echo 'window setTitle: @"Hallo&_wie&_geht&_es&_dir?";';
    echo 'NSBundle loadNibNamed:owner: @"MyDocument" self;';
    
    $pipe = fopen("/tmp/pop_pipe", 'r');
    
    while(1){
        $line = trim(fread($pipe,1024));
        if($line){
            echo "# $line;";
            if($line == "exec (unknownSender) exampleAction:"){
                echo "exec textfield setStringValue: @'My&_Name&_is';";
            }
        }
        usleep(100000);
    }
    
    
    ?>