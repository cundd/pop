#!/usr/bin/env php
<?php
    
    //    echo "new NSObject" . PHP_EOL;
    //    echo "exec window close" . PHP_EOL;
    //    sleep(1);
    //    
    //    echo "new NSWindow b noInit" . PHP_EOL;
    //    echo "exec b initWithContentRect:styleMask:backing:defer: @NSMakeRect(0,0,200,200) (uint)13 (uint)2 (int)1" . PHP_EOL;
    //    
    //    echo "exec b makeKeyAndOrderFront: nil";
    //    
    //    echo "get b";
    
    ?>




new NSObject
exec window close
new NSWindow myWindow noInit
exec myWindow initWithContentRect:styleMask:backing:defer: @NSMakeRect(0,0,400,200) (uint)13 (uint)2 (int)1

myWindow setTitle: @"Hallo&nbsp%wie&nbsp%geht&nbsp%es&nbsp%dir?"

exec myWindow makeKeyAndOrderFront: nil

