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

On startup the delegate will launch a PHP task with the script path defined in the taskScriptPath property, which defaults to "path/to/the/app/resources/qoq/run.php". This path can be easily overwritten in the -taskScriptPath method of your delegate.

Commands / Communicate with POP
-------------------------------

The PHP script communicates with the POP server through the PHP tasks standard output. So everything PHP writes to that pipe through echo(), print(), printf(), fwrite(STDOUT, "some command"), or similar will be evaluated by POP.

Commands consist of the different parts:

    {signal} {part1} {part2} … {partN} ;

It starts with a signal, that describes the task to perform, and is followed by the parts/arguments needed to perform the task. The different parts of a command are separated by a whitespace character (" ") and the command itself is delimited by a newline character or a semicolon (";").


### Comments

    // This is a comment until a semicolon (";") or a newline occurs
    # This is also a comment until a semicolon (";") or a newline occurs
    > At the moment this is a comment, but this may be removed in future versions
Currently these three comment styles are available.

### alloc

    alloc {class} {identifier}
Allocates a new instance of the given {class} and safes it with the name {identifier}.

### new

    new {class} {identifier} {noInit}
Allocates a new instance of the given {class}, safes it with the name {identifier} and invokes it's -init method.
If {noInit} is set the -init method will not be called (this is the same as the alloc command).

### exec

    exec {identifier} {name:of:the:method:} {argument1}, … {argumentN}
Invokes the method {name:of:the:method:} of the object with the identifier {identifier} and the given arguments.

    {identifier} {name:of:the:method:} {argument1}, … {argumentN}
The short version of the exec command.

### set

    set {identifier} {identifierOfTheNewValue}
Replaces the variable {identifier} with the value of {identifierOfTheNewValue}.

### get

    get {identifier}
The POP server exports the value of {identifier} to the PHP script.

### echo

    echo {identifier}
Logs the value of {identifier} to the console.

### printf

    printf {identifier} {format}
Logs the value of {identifier} with the format {format} to the console.

### throw

    throw {name} {message} [{userInfo}]
Throws an Objective-C exception with the name {name} and reason {message}. 
If the optional {userInfo} is given, the value of {userInfo} will be used as the exception's userInfo.

### Command examples

#### Close the delegate's window

    echo "exec window close;";

You may omit 'exec'.

    echo "window close;";

#### Change the title of the delegate's window

    echo 'window setTitle: @"NewTitle";';

Whitespaces are used to separate the parts of a command, therefore you have to replace whitespaces with "&_".
    
    echo 'window setTitle: @"Hello&_how&_are&_you?";';

#### Create a new window
    echo "new NSWindow theIdentifier noInit;";
    echo "exec theIdentifier initWithContentRect:styleMask:backing:defer: @NSMakeRect(0,0,200,200) (uint)13 (uint)2 (int)1;";
    echo "exec theIdentifier makeKeyAndOrderFront: nil;";

Communicate with QOQ
--------------------

A one way communication would not be enough to create useful applications. This is what QOQ is created for. It provides a runtime that manages the runloop and the basic communication with the POP server. So your PHP application is able to receive commands from Cocoa, that as an example signal the click of a button.

On startup POP creates a pipe with the path defined in the qoqPipeName property, which defaults to "/tmp/qoq_pipe". The server exports data and commands through this pipe and QOQ is configured to read new commands from this pipe. 

### Principe of retrieving values
- The PHP task sends the command ```get window.title```.
- POP receives the command, evaluates it and retrieves the title of the delegate's window property
- POP writes the window's title string to the QOQ pipe
- The PHP script has to wait until this data has been written to the pipe
- The PHP script can use the received value

### Retrieving values with QOQ
To retrieve values with QOQ you can use the runtime directly

    \Qoq\QoqRuntime::getValueForKeyPath('window.title');

or even simpler, create a subclass of the AbstractController

    use \Qoq\Controller\AbstractController;
    class StandardController extends AbstractController {
    	public function handle($command){
            $oldTitle = $this->getValueForKeyPath('window.title');
        }
    }

Exposing your Cocoa code through Plugins
----------------------------------------

Sometimes one would like to define custom commands and signals to enhance the abilities of POP, or to create shortcuts that invoke multiple methods at once. Here Plugins step into the breach. You can register your own classes and methods to be invoked when a specified command is parsed.

When the POP server receives a command, which has no built-in route, the server will check if a handler is registered for the given command. If at least one handler is registered POP will post a notification which triggers the invocation of the handler's method.

### Registering a Plugin
The PopServer's method +sharedInstance may be used to retrieve the shared server instance.

    [[PopServer sharedInstance] addPlugin:{handler} 
                                selector:@selector({method:}) 
                                forCommand:@"{command}"];

### Definition of a Plugin method
The methods that should handle the command will receive a notification object that contains the original command's data.

    -(void)theHandlerMethod: (NSNotification *)notification{
        // notification.userInfo holds the command's data
    }

The userInfo dictionary contains the command parts as a NSArray object for the key "commandParts" and the signal for key "signal".

    NSDictionary * userInfo = [NSDictionary dictionaryWithObjectsAndKeys:
                            commandParts, @"commandParts", 
                            signal, @"command",
                            nil];

Issues and Todos
----------------

- POP cannot invoke Objective-C class methods.
- POP cannot export non-scalar Objective-C objects (maybe include a JSON library).