//
//  CDAppDelegate.h
//  pop
//
//  Created by Daniel Corn on 02.05.12.
//
//    Permission is hereby granted, free of charge, to any person obtaining a 
//    copy of this software and associated documentation files (the "Software"), 
//    to deal in the Software without restriction, including without limitation 
//    the rights to use, copy, modify, merge, publish, distribute, sublicense, 
//    and/or sell copies of the Software, and to permit persons to whom the 
//    Software is furnished to do so, subject to the following conditions:
//
//    The above copyright notice and this permission notice shall be included in 
//    all copies or substantial portions of the Software.
//
//    THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR 
//    IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, 
//    FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL 
//    THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER 
//    LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING 
//    FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER 
//    DEALINGS IN THE SOFTWARE.

#import <Cocoa/Cocoa.h>
#import <sys/stat.h>
#import "PopProxyObject.h"


#ifndef kCDInteractiveTimerInterval
#define kCDInteractiveTimerInterval 0.1
#endif

#ifndef kCDNamedPipe
#define kCDNamedPipe "/tmp/qoq_pipe"
#endif

#ifndef kCDInteractivePrompt
#define kCDInteractivePrompt @"> "
#endif

#ifndef USE_NCURSES
#define USE_NCURSES 0
#endif

// Print profiling information
#ifndef SHOW_PROFILING
#define SHOW_PROFILING 0
#endif


// Print debug information
// Possible values:
//  0: Don't print debug information
//  1: Print only some information
//  2: Print all debug information
// WARNING: Some Objective-C objects (i.e. NSURL) must not be print, 
// before they are initialized.
#ifndef SHOW_DEBUG_INFO
#define SHOW_DEBUG_INFO 0
#endif


#if USE_NCURSES
#import <ncurses.h>
#endif


typedef enum {
    CDPopModeNormal = 0,
    CDPopModeInteractive = 1
} CDPopMode;

extern NSString * const PopNotificationNamePrefix;
extern NSString * const PopNotificationNameUnfoundCommandPrefix;

@interface PopServer : NSObject <NSApplicationDelegate> {
    NSString *qoqUnknownSenderArgument;
    CDPopMode mode;
    NSTask *task;
    
    // The File Handle for reading from PHP
	NSFileHandle *popReadHandle;
	NSFileHandle *writeHandle;
    
    // The File Handle for writing to PHP
    NSFileHandle *qoqWriteHandle;
    
    // Configuring the task
    NSString *taskLaunchPath;
	NSString *taskScriptPath;
    NSString *qoqPipeName;
    NSMutableArray *taskArguments;
    
    // Pool for plugins
    NSMutableDictionary *pluginPool;
    
    NSString *commandQueue;
    NSCharacterSet *commandDelimiter;
    BOOL didSendResponseForCommand;
    
    NSMutableDictionary *objectPool;
    BOOL targetIsClass;
    
    NSString *lastCommand;
}


/** @name Initialization */
/**
 * Returns the mode in which to run.
 *
 * Either CDPopModeNormal or CDPopModeInteractive
 * 
 * @return
 */
@property (assign) CDPopMode mode;

/**
 * The object pool property.
 * 
 * @return
 */
@property (retain) NSMutableDictionary *objectPool;

/**
 * Returns the path to the script that will be passed to the task as an
 * argument.
 *
 * @return
 */
@property (readonly) NSString *taskScriptPath;

/**
 * Returns the launch path of the task.
 * 
 * @return
 */
@property (readonly) NSString *taskLaunchPath;

/**
 * Returns the name to the named pipe to send data to QOQ.
 * 
 * @return
 */
@property (readonly) NSString *qoqPipeName;

/**
 * Returns the array of arguments passed to the task.
 * 
 * @return
 */
@property (readonly) NSMutableArray *taskArguments;

/**
 * Returns if the application is allowed to run interactive.
 * 
 * @return
 */
@property (readonly) BOOL allowInteractive;

/**
 * Returns if the application is allowed to run commands from a script passed,
 * as argument.
 * 
 * @return
 */
@property (readonly) BOOL allowFileInput;


/** @name Argument handling */
/**
 * Transforms the parts of a command into arguments.
 *
 * Arguments with simple variable types, that are i.e. prepended with "(float)",
 * "(int)" or "(uin)" will be again processed when they are added as arguments
 * to the NSInvocation instance (see -handleSimpleArgument:forInvokation:atIndex:)
 *
 * @param 	pathParts		The arguments from the command parts
 * @return 					Returns an array of transformed arguments
 */
- (NSArray *)commandPartsToArguments:(NSArray *)pathParts;

/**
 * Transforms the given argument to the signaled simple variable type and adds
 * them to the invocation at the given index.
 *
 * Example:
 *  The command argument
 *  	@"(uint)1"
 *  will add
 *  	(uint)1
 *  to the invocation.
 *
 * @param 	argument		The argument string to transform
 * @param 	invocation		The invocation the argument will be added to
 * @param 	index			The index of the invocations argument to set
 */
- (void)handleSimpleArgument:(NSString *)argument forInvokation:(NSInvocation * )invocation atIndex:(NSUInteger)index;

/**
 * Transforms the given special argument.
 *
 * Example:
 *  The command argument
 *  	@"@NSMakeRect(0,0,200,200)"
 *  will add the result of
 *  	NSMakeRect(0, 0, 200, 200)
 *  to the invocation.
 *
 *  The command argument
 *  	@"@'My&_Name&_is'"
 *  will add 
 *  	@"My Name is"
 *  to the invocation.
 *
 * @param 	argument		The argument string to transform
 * @param 	invocation		The invocation the argument will be added to
 * @param 	index			The index of the invocations argument to set
 */
- (void)handleSpecialArgument:(NSString *)argument forInvokation:(NSInvocation * )invocation atIndex:(NSUInteger)index;
- (NSString *)transformString:(NSString *)inputString;


/** @name Object management */
/**
 * Looks for the unique identifier for the given object.
 * 
 * @param 	object		The object to get the search for
 * @return				Returns the unique identifier if one is found, otherwise qoqUnknownSenderArgument
 */
- (NSString *)identifierForObject:(id)object;

/**
 * Looks for the unique identifier for the given object in the object pool.
 * 
 * @param 	object		The object to get the search for
 * @return				Returns the unique identifier if one is found, otherwise nil
 */
- (NSString *)identifierForObjectInPool:(id)object;

/**
 * Currently not implemented.
 * 
 * @param 	object		The object to get the search for
 * @return				Returns nil
 */
- (NSString *)identifierForObjectInProperties:(id)object;

/**
 * Looks for the object with the given unique identifier.
 *
 * @param 	identifier		The unique identifier to get the object for
 * @return 					Returns the found object if found, otherwise nil
 */
- (id)findObjectInPoolWithIdentifier:(NSString *)identifier;

/**
 * Looks for the object with the given unique identifier.
 *
 * In contrast to -findObjectInPoolWithIdentifier: properties will also be
 * taken into account.
 * 
 * @param 	identifier		The unique identifier to get the object for
 * @return 					Returns the found object if found, otherwise nil
 */
- (id)findObjectWithIdentifier:(NSString *)identifier;

/**
 * Sets the given object for the unique identifier in the object pool.
 *
 * @param 	object 			The object to set
 * @param 	identifier		The unique identifier to save the object with
 */
- (void)setObject:(id)object inPoolWithIdentifier:(NSString *)identifier;

/**
 * Sets the given object for the unique identifier.
 *
 * In contrast to -setObject:inPoolWithIdentifier: properties will also be
 * taken into account.
 *
 * @param 	object 			The object to set
 * @param 	identifier		The unique identifier to save the object with
 */
- (void)updateObject:(id)object forIdentifier:(NSString *)identifier;


/** @name Parsing commands */
/**
 * Splits and routes the received command.
 *
 * This is the central method of POP. The received and prepared command string
 * will be split into the signal and the arguments. Then the arguments will be
 * routed according to the signal.
 *
 * @param 	commandString 	The prepared command string to parse
 * @return					Returns TRUE if the command was successfully routed and performed, otherwise FALSE
 */
- (BOOL)parseCommandString:(NSString *)commandString;

/**
 * Invokes the method on the object, both taken from the given command parts.
 *
 * This is the method which handles the 'exec' signal.
 *
 * @param	commandParts	The previously parsed parts of the command string
 * @return 					Returns TRUE if the command could be successfully executed, otherwise FALSE
 */
- (BOOL)executeWithCommandParts:(NSArray *)commandParts;


/** @name Sending commands */
/**
 * Sends the given command string to QOQ.
 * 
 * @param	theCommand 		The command to send
 * @param	theSender		The sender
 */
- (void)sendCommand:(NSString *)theCommand sender:(id)theSender;

/**
 * Sends the given command string to QOQ.
 * 
 * @param	theCommand 		The command to send
 */
- (void)sendCommand:(NSString *)theCommand;

/**
 * Sends the given object to QOQ.
 * 
 * @param 	theObject	The object to export
 */
- (void)sendObject:(id)theObject;

/**
 * Sends the void message object to QOQ.
 */
- (void)sendVoid;


/** @name Task management */
/**
 * Invoked when the POP server did receive data from QOQ.
 * 
 * @param 	aNotification	The notification
 */
- (void)receiveData:(NSNotification *)aNotification;

/**
 * Invoked when the QOQ task did terminate.
 * 
 * @param 	notif	The notification
 */
- (void)taskDidTerminate:(NSNotification *)notif;

/**
 * Stops the QOQ task.
 *
 * This method will be invoked when the POP application will terminate.
 *
 * @param 	notif	The notification
 */
- (void)stopTask:(NSNotification *)notif;

/**
 * Stops the QOQ task.
 */
- (void)stopTask;

/**
 * Starts the QOQ task.
 */
- (void)startTask;


/** @name Interactive */
/**
 * Starts the interactive run loop.
 *
 * @return     
 */
- (BOOL)runInteractive;

/**
 * The interactive run loop.
 *
 * @param aTimer The timer firing the method
 */
- (void)runInteractiveLoop:(NSTimer *)aTimer;

/**
 * Stop the interactive mode and clean up the shell.
 */
- (void)finishInteractive;

/**
 * Stop the interactive mode and clean up the shell.
 *
 * @param aNotification The notification sent when the application will terminate
 */
- (void)finishInteractive:(NSNotification *)aNotification;

/**
 * Returns the input from the command line.
 *
 * @return
 */
- (NSString *)getInputFromCommandLine;

/**
 * Used to initialize ncurses, if it is enabled.
 */
- (void)initEnvironment;

#if USE_NCURSES
/**
 * Returns the main ncurses window.
 *
 * @return
 */
-(WINDOW *)getNCWindow;
#endif



/** @name Plugins */
/**
 * Returns if a Plugin is registered for the given command.
 * 
 * @param command The command that may be handled by a Plugin
 */
- (BOOL)hasPluginForCommand:(NSString *)command;

/**
 * Forwards the command the according Plugins.
 *
 * @param commandParts The array of command parts the POP server received.
 */
- (BOOL)pluginHandleCommandParts:(NSArray *)commandParts;

/**
 * Registers the given Plugin und Plugin method for the command.
 *
 * @param handler 	The Plugin instance
 * @param selector 	The selector of the Plugin method to invoke
 * @param command	The command on which the Plugin will be used
 */
- (void)addPlugin:(id)handler selector:(SEL)selector forCommand:(NSString *)command;


/** @name Helper */
/**
 * Returns if the POP server instance if KVC compliant for the given key.
 *
 * @param	keyPath		The key to check
 * @return Returns TRUE if the server will return a value for the given key, otherwise FALSE
 */
- (BOOL)isKVCCompliantForKey:(NSString *)keyPath;

/**
 * Invokes the method with the given name on the object, with an array of
 * unprepared arguments.
 * 
 * @param methodName 	The name of the method to invoke
 * @param object		The object that should respond to the method
 * @param arguments		The arguments for the method
 * @return				Returns TRUE if the method has been invoked successfully, otherwise FALSE
 */
- (BOOL)invokeMethodWithName:(NSString *)methodName onObject:(id)object withArguments:(NSArray *)arguments;

/**
 * Invokes selector on the class with the given name and prepared arguments.
 *
 * In contrast to -invokeMethodWithName:onObject:withArguments: the method only
 * allows objects of type id. The arguments will not be parsed or casted to
 * simple data types.
 * 
 * @param className 	The class name
 * @param aSelector		The selector to invoke
 * @param arguments		The arguments for the method
 * @return				Returns TRUE if the method has been invoked successfully, otherwise FALSE
 */
- (BOOL)invokeClassMethodWithName:(NSString *)className selector:(SEL)aSelector withArguments:(NSArray *)arguments;

/**
 * Returns if the given identifier seems to be a class name
 * 
 * @param identifier	The identifier to test
 * @return
 */
- (BOOL)identifierSignalsClass:(NSString *)identifier;

/**
 * Handles the return value of the given invocation and method signature
 *
 * @param invocation    The invocation object
 * @return              Returns if the return value has been handled successfully
 */
- (BOOL)handleReturnValueForInvocation:(NSInvocation *)invocation;


/** @name Preparing raw commands */
/**
 * The default implementation passes the input to -cleanupString: and returns
 * the result.
 *
 * @param commandInput 	The command string to prepare
 * @return					Returns a prepared copy of the input
 */
- (NSString *)prepareRawCommandInput:(NSString *)commandInput;

/**
 * Trims whitespaces and control characters from a previously cleaned up input
 * string.
 *
 * @param commandString 	The command string to prepare
 * @return					Returns a prepared copy of the input
 */
- (NSString *)prepareCommandString:(NSString *)commandString;

/**
 * Returns a copy of the input string with all characters removed, that don't
 * pass the regular expression.
 *
 * @param 	input	The string to clean up
 * @return			Returns a prepared copy of the input
 */
- (NSString *)cleanupString:(NSString *)input;


/** @name Profiler */
/**
 * Prints a simple profiling message
 */
+ (void)profile;

/**
 * Prints a simple profiling message
 *
 * @param message   A message to display
 */
+ (void)profile:(NSString *)message;

/**
 * Prints a simple profiling message and an additional warning if the last 
 * routine took longer than the given warning interval.
 *
 * @param message           A message to display
 * @param warning           A warning to display when the last routine was too long
 * @param warningInterval   Warn when the routine took longer than this value
 */
+ (void)profile:(NSString *)message andShowWarning:(NSString *)warning afterTimeInterval:(NSTimeInterval)warningInterval;



/** @name Shared instance */
/**
 * Returns a shared instance of the POP server.
 *
 * @return
 */
+ (PopServer *)sharedInstance;

@end
