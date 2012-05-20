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

typedef enum {
    CDPopModeNormal = 0,
    CDPopModeInteractive = 1
} CDPopMode;

extern NSString * const PopNotificationNamePrefix;
extern NSString * const PopNotificationNameUnfoundCommandPrefix;

@interface PopServer : NSObject <NSApplicationDelegate> {
    NSString *opoUnknownSenderArgument;
    CDPopMode mode;
    NSTask *task;
    
    // The File Handle for reading from PHP
	NSFileHandle *popReadHandle;
	NSFileHandle *writeHandle;
    
    // The File Handle for writing to PHP
    NSFileHandle *opoWriteHandle;
    
    // Configuring the task
    NSString *taskLaunchPath;
	NSString *taskScriptPath;
    NSMutableArray *taskArguments;
    
    // Pool for plugins
    NSMutableDictionary *pluginPool;
    
    NSString *commandQueue;
    NSCharacterSet *commandDelimiter;
    
    NSMutableDictionary *objectPool;
    BOOL targetIsClass;
}

@property (assign) CDPopMode mode;
@property (retain) NSMutableDictionary *objectPool;

@property (readonly) NSString *taskScriptPath;
@property (readonly) NSArray *taskArguments;
@property (readonly) NSString *taskLaunchPath;

- (NSArray *)commandPartsToArguments:(NSArray *)pathParts;
- (void)handleSimpleArgument:(NSString *)argument forInvokation:(NSInvocation * )invocation atIndex:(NSUInteger)index;
- (void)handleSpecialArgument:(NSString *)argument forInvokation:(NSInvocation * )invocation atIndex:(NSUInteger)index;
- (NSString *)transformString:(NSString *)inputString;

- (NSString *)identifierForObject:(id)object;
- (NSString *)identifierForObjectInPool:(id)object;
- (NSString *)identifierForObjectInProperties:(id)object;

- (id)findObjectInPoolWithIdentifier:(NSString *)identifier;
- (void)setObject:(id)object inPoolWithIdentifier:(NSString *)identifier;
- (id)findObjectWithIdentifier:(NSString *)identifier;
- (void)updateObject:(id)object forIdentifier:(NSString *)identifier;

- (BOOL)executeWithCommandParts:(NSArray *)commandParts;
- (BOOL)parseCommandString:(NSString *)commandString;

- (void)sendCommand:(NSString *)theCommand sender:(id)theSender;
- (void)sendCommand:(NSString *)theCommand;
- (void)forwardInvocation:(NSInvocation *)invocation;

- (void)receiveData:(NSNotification *)aNotification;
- (void)taskDidTerminate:(NSNotification *)notif;
- (void)stopTask:(NSNotification *)notif;
- (void)stopTask;

- (BOOL)runInteractive;
- (void)runInteractiveLoop:(NSTimer *)aTimer;

- (BOOL)pluginHandleCommandParts:(NSArray *)commandParts;
- (BOOL)hasPluginForCommand:(NSString *)command;
- (void)addPlugin:(id)handler selector:(SEL)selector forCommand:(NSString *)command;

- (BOOL)isKVCCompliantForKey:(NSString *)keyPath;
- (BOOL)invokeMethodWithName:(NSString *)methodName onObject:(id)object withArguments:(NSArray *)arguments;
- (BOOL)identifierSignalsClass:(NSString *)identifier;

- (NSString *)prepareRawCommandInput:(NSString *)commandInput;
- (NSString *)prepareCommandString:(NSString *)commandString;
- (NSString *)cleanupString:(NSString *)input;

+ (PopServer *)sharedInstance;

@end