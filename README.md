POP - Cocoa with vitamin P
==========================

Introduction
------------

As a PHP developer and Mac user you may be jealous of Ruby and Python programmers, which can use [MacRuby](http://macruby.org/) or [PyObjC](http://pyobjc.sourceforge.net/). pop and it's counterpart qoq now offers you the ability to let your Cocoa application communicate with the PHP application and vice versa.

POP isn't a real PHP Objective-C Bridge, like it's the aim of the [php-objc project](http://wezfurlong.org/blog/2007/nov/php-objective-c-bridge/), which doesn't seem to work on Mac OS X Lion. POP utilizes the flexibility of Objective-C to perform tasks given by a string. These strings (called command) are sent from PHP using it's standard output. To test these commands POP offers a very simple interactive console.

    {path/to/your/executable} -a

pop vs. qoq
-----------

The POP system consists of two parts:
- POP: The Cocoa application that listens for commands sent from PHP
- QOQ: The PHP framework that provides the runtime of the PHP task

If the functionality of QOQ doesn't meet your needs, you may replace it with your own PHP runtime.

Installation
------------

To use POP simply add the PopServer.h and PopServer.m files to your project and let your Cocoa delegate extend the PopServer.

    @interface CDAppDelegate : PopServer <NSApplicationDelegate> {
    }

On launch the delegate will launch a PHP task with the script from {path/to/the/app/resources/qoq/run.php}. The path to the script can be overwritten in the -taskScriptPath method of your delegate.

Commands
--------

### alloc {class} {identifier}
Allocate a new instance of the given {class} and safe it with the name {identifier}

### new {class} {identifier} {noInit}
Allocate a new instance of the given {class}, safe it with under {identifier} and invoke it's -init method
If {noInit} is set the -init method will not be called (this is the same as the alloc command).

### exec {identifier} {name:of:the:method:} {argument1}, … {argumentN}
Invoke the method {name:of:the:method:} of the object with the identifier {identifier} and the given arguments.

### {identifier} {name:of:the:method:} {argument1}, … {argumentN}
The short version of the exec command.

### set {identifier} {identifierOfTheNewValue}
Replace the variable {identifier} with the value of {identifierOfTheNewValue}.

### get {identifier}
If this command is called the PopServer will export the value of {identifier} to the PHP script.

Examples
--------

### Close the delegates window
    echo "exec window close;";
You may omit 'exec'
    echo "window close;";

### Change the title of the delegates window
    echo 'window setTitle: @"NewTitle";';

Whitespaces are used to separate the parts of a command, therefore you have to replace whitespaces with "&_".
    echo 'window setTitle: @"Hello&_how&_are&_you?";';

### Create a new window
    echo "new NSWindow theIdentifier noInit;";
    echo "exec theIdentifier initWithContentRect:styleMask:backing:defer: @NSMakeRect(0,0,200,200) (uint)13 (uint)2 (int)1;";
    echo "exec theIdentifier makeKeyAndOrderFront: nil;";

