//
//  CDAppDelegate.h
//  pop
//
//  Created by Daniel Corn on 02.05.12.
//  Copyright (c) 2012 cundd. All rights reserved.
//

#import <Cocoa/Cocoa.h>

typedef enum {
    CDPopModeNormal = 0,
    CDPopModeInteractive = 1
} CDPopMode;

@interface CDAppDelegate : NSObject <NSApplicationDelegate> {
    CDPopMode mode; 
    NSTask *task;
	NSFileHandle *readHandle;
	NSFileHandle *writeHandle;
    
	NSString *scriptPath;
    
    NSString *commandQueue;
    NSCharacterSet *commandDelimiter;
    
    NSMutableDictionary *objectPool;
    BOOL targetIsClass;
}


@property (assign) IBOutlet NSWindow *window;
@property (retain) NSMutableDictionary *objectPool;

- (NSArray *)commandPartsToArguments:(NSArray *)pathParts;

- (NSString *)transformString:(NSString *)inputString;

- (BOOL)invokeMethodWithName:(NSString *)methodName onObject:(id)object withArguments:(NSArray *)arguments;

- (id)findObjectInPoolWithIdentifier:(NSString *)identifier;

- (void)setObject:(id)object inPoolWithIdentifier:(NSString *)identifier;

- (id)findObjectWithIdentifier:(NSString *)identifier;

- (void)updateObject:(id)object forIdentifier:(NSString *)identifier;

- (BOOL)executeWithCommandParts:(NSArray *)commandParts;

- (BOOL)parseCommandString:(NSString *)commandString;

- (void)receiveData:(NSNotification *)aNotification;

- (void)taskDidTerminate:(NSNotification *)notif;

- (BOOL)runInteractive;
-(void)runInteractiveLoop:(NSTimer *)aTimer;


- (void)handleSimpleArgument:(NSString *)argument forInvokation:(NSInvocation * )invocation atIndex:(NSUInteger)index;

- (void)handleSpecialArgument:(NSString *)argument forInvokation:(NSInvocation * )invocation atIndex:(NSUInteger)index;

- (BOOL)isKVCCompliantForKey:(NSString *)keyPath;

- (NSString *)cleanupString:(NSString *)input;



@end
