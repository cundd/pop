POP - Cocoa with vitamin P
==========================

Introduction
------------

As a PHP developer and Mac user you may be jealous of Ruby and Python programmers, which can use [MacRuby](http://macruby.org/) or [PyObjC](http://pyobjc.sourceforge.net/). pop and it's counterpart qoq now offers you the ability to let your Cocoa application communicate with the PHP application and vice versa.

POP isn't a real PHP Objective-C Bridge, like it's the aim of the [php-objc project](http://wezfurlong.org/blog/2007/nov/php-objective-c-bridge/), which doesn't seem to work on Mac OS X Lion. POP utilizes the flexibility of Objective-C to perform tasks given by a string. These strings (called command) are sent from PHP using it's standard output. To test these commands POP offers a very simple interactive console.

    `{path/to/your/executable} -a`

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