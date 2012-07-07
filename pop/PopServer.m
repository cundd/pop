//
//  CDAppDelegate.m
//  pop
//
//  Created by Daniel Corn on 02.05.12.
//
//    Copyright (c) 2012 Daniel Corn
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

#import "PopServer.h"


#pragma mark Constants
NSString * const PopNotificationNamePrefix = @"PopNotification";
NSString * const PopNotificationNameUnfoundCommandPrefix = @"PopNotificationUnfoundCommand";


#pragma mark Say
void say(NSString *format, ...){
    va_list      listOfArguments;
    NSString    *formattedString;

    va_start(listOfArguments, format);
    formattedString = [[NSString alloc] initWithFormat:format
                                             arguments:listOfArguments];
    va_end(listOfArguments);
    
    if([(PopServer *)[PopServer sharedInstance] mode] == CDPopModeInteractive){
        // Color the output in Cyan
        printf("\033[36m%s\033[0m", [formattedString UTF8String]);
    } else {
        printf("%s", [formattedString UTF8String]);
    }
}


@implementation PopServer

@synthesize objectPool, mode;

static PopServer *sharedPopServerInstance = nil;

#pragma mark Argument handling
- (NSArray *)commandPartsToArguments:(NSArray *)pathParts{
    NSUInteger i;
    NSUInteger length = [pathParts count];
    NSMutableArray * arguments = [NSMutableArray arrayWithCapacity:length];
    if(length == 2){
        return [NSArray array];
    }
    for(i = 2; i<length; i++){
        id newArgument;
        NSString * argumentIdentifier = [pathParts objectAtIndex:i];
        
        if([argumentIdentifier isEqualToString:@"self"]){ // Self
            newArgument = self;
        } else if([argumentIdentifier hasPrefix:@"@"] || [argumentIdentifier hasPrefix:@"("]){ // Check if it is a special or simple type argument
            newArgument = argumentIdentifier;
        } else if([argumentIdentifier hasPrefix:@"'"]){ // Check if quoted with single quote ("'")
            newArgument = [argumentIdentifier stringByReplacingOccurrencesOfString:@"'" withString:@""];
        } else if([argumentIdentifier hasPrefix:@"\""]){ // Check if quoted with double quote (""")
            newArgument = [argumentIdentifier stringByReplacingOccurrencesOfString:@"\"" withString:@""];
        } else {
            newArgument = [self findObjectWithIdentifier:argumentIdentifier];
            if(!newArgument){
                newArgument = argumentIdentifier;
            }
        }
        
        [arguments addObject:newArgument];
    }
    return [NSArray arrayWithArray:arguments];
}

- (void)handleSimpleArgument:(NSString *)argument forInvokation:(NSInvocation * )invocation atIndex:(NSUInteger)index {
    if([argument hasPrefix:@"(float)"]){
        float value = [[argument substringFromIndex:7] floatValue];
        [invocation setArgument:&value atIndex:index];
    } else if([argument hasPrefix:@"(int)"]){
        int value = [[argument substringFromIndex:5] intValue];
        [invocation setArgument:&value atIndex:index];
    } else if([argument hasPrefix:@"(integer)"]){
        int value = [[argument substringFromIndex:9] intValue];
        [invocation setArgument:&value atIndex:index];
    } else if([argument hasPrefix:@"(uint)"]){
        NSUInteger value = [[argument substringFromIndex:6] intValue];
        [invocation setArgument:&value atIndex:index];
    } else if([argument hasPrefix:@"(uinteger)"]){
        NSUInteger value = [[argument substringFromIndex:10] intValue];
        [invocation setArgument:&value atIndex:index];
    }
}

- (NSString *)transformString:(NSString *)inputString{
    return [inputString stringByReplacingOccurrencesOfString:@"&_" withString:@" "];
}

- (void)handleSpecialArgument:(NSString *)argument forInvokation:(NSInvocation * )invocation atIndex:(NSUInteger)index {
    if([argument hasPrefix:@"@NSMakeRect("]){
        NSUInteger length = [argument length] - 12 - 1;
        argument = [[argument substringWithRange:NSMakeRange(12, length)] stringByReplacingOccurrencesOfString:@" " withString:@""];
        NSArray *rectPoints = [argument componentsSeparatedByString:@","];
        NSRect rect = NSMakeRect(
                                 [[rectPoints objectAtIndex:0] floatValue], 
                                 [[rectPoints objectAtIndex:1] floatValue], 
                                 [[rectPoints objectAtIndex:2] floatValue], 
                                 [[rectPoints objectAtIndex:3] floatValue]
                                 );
        [invocation setArgument:&rect atIndex:index];
    } else if([argument hasPrefix:@"@NSMakePoint("]){
        NSUInteger length = [argument length] - 13 - 1;
        argument = [[argument substringWithRange:NSMakeRange(12, length)] stringByReplacingOccurrencesOfString:@" " withString:@""];
        NSArray *points = [argument componentsSeparatedByString:@","];
        NSPoint point = NSMakePoint(
                                 [[points objectAtIndex:0] floatValue], 
                                 [[points objectAtIndex:1] floatValue]
                                 );
        [invocation setArgument:&point atIndex:index];

    } else if([argument hasPrefix:@"@\""] || [argument hasPrefix:@"@'"]){
        NSUInteger length = [argument length] - 2 - 1;
        argument = [argument substringWithRange:NSMakeRange(2, length)];
        
        argument = [self transformString:argument];
        
        [invocation setArgument:&argument atIndex:index];
    }
}


#pragma mark Object management
- (NSString *)identifierForObject:(id)object{
    NSString *identifier;
    
    // Look inside the pool
    identifier = [self identifierForObjectInPool:object];
    if(!identifier){
        identifier = qoqUnknownSenderArgument;
    }
    return identifier;
}
- (NSString *)identifierForObjectInPool:(id)object{
    NSArray *allKeysForObject;
    
    allKeysForObject = [objectPool allKeysForObject:object];
    if(allKeysForObject.count < 1){
        return nil;
    }
    return [allKeysForObject objectAtIndex:0];
}
- (NSString *)identifierForObjectInProperties:(id)object{
    return nil;
}

- (id)findObjectInPoolWithIdentifier:(NSString *)identifier{
#if SHOW_DEBUG_INFO
    NSLog(@"Identifier: %@ Pool: %@", identifier, objectPool);
#endif
    return [objectPool objectForKey:identifier];
}

- (void)setObject:(id)object inPoolWithIdentifier:(NSString *)identifier{
    [objectPool setObject:object forKey:identifier];
}

- (void)updateObject:(id)object forIdentifier:(NSString *)identifier{
    // Check if the identifier belongs to a property
    if([self respondsToSelector:NSSelectorFromString(identifier)]){
        [self setValue:object forKeyPath:identifier];
    } else {
        [self setObject:object inPoolWithIdentifier:identifier];
    }
}

- (id)findObjectWithIdentifier:(NSString *)identifier{
    NSObject * object;
    NSString * firstPart, * secondPart;
    NSRange range;
    
    range = [identifier rangeOfString:@"."];
    if(range.location == NSNotFound){
        firstPart = identifier;
    } else {
        firstPart = [identifier substringToIndex:range.location];
        secondPart = [identifier substringFromIndex:range.location + 1];
    }
    if([self identifierSignalsClass:identifier]){
        object = identifier;
    } else {
        object = [self findObjectInPoolWithIdentifier:firstPart];
    }
    if(!object){
        if([self isKVCCompliantForKey:firstPart]){
            object = [self valueForKeyPath:firstPart];
        }
    }
    if(range.location != NSNotFound){
        return [object valueForKeyPath:secondPart];
    }
    return object;
}


#pragma mark Helper
- (BOOL)isKVCCompliantForKey:(NSString *)keyPath{
    NSArray * keyPathArray = [keyPath componentsSeparatedByString:@"."];
    NSString * firstKey = [keyPathArray objectAtIndex:0];
    if([self respondsToSelector:NSSelectorFromString(firstKey)]){
        return TRUE;
    }
    return FALSE;
}

- (BOOL)identifierSignalsClass:(NSString *)identifier{
    // Check if the first character is upper case
    if([identifier length] == 0){
        return FALSE;
    }
    unichar firstChar = [identifier characterAtIndex: 0]; //get the first character from the string.
    NSCharacterSet *upperCaseSet = [NSCharacterSet uppercaseLetterCharacterSet];
    if ([upperCaseSet characterIsMember: firstChar]){
        return TRUE;
    }
    return FALSE;
}

- (BOOL)invokeMethodWithName:(NSString *)methodName onObject:(NSObject *)object withArguments:(NSArray *)arguments{
    BOOL success = TRUE;
    NSUInteger argumentCount, argumentIndex, i;
    NSInvocation *invocation;
    NSMethodSignature *signature;
    SEL selector;
    
    // First attempt to create the method signature with the provided selector.
    selector = NSSelectorFromString(methodName);
    
    if(targetIsClass){
#if !DEBUG
        // If debug mode is not enabled skip the rest
        NSLog(@"The target is a class, this is not supported");
        return FALSE;
#endif
        Class targetClass = NSClassFromString((NSString *)object);
        signature = [targetClass instanceMethodSignatureForSelector:selector];
        NSLog(@"Target is class %@ %@ %@ %s", object, targetClass, signature, (char *)selector);
    } else {
        signature = [object methodSignatureForSelector:selector];
    }
    if (!signature) {
        NSLog(@"%@: Method signature could not be created for name %@.", object, methodName);
        return FALSE;
    }
    
#if SHOW_DEBUG_INFO
    NSLog(@"Args: %@", arguments);
#endif
    
    // Next we create the invocation that will actually call the required selector.
    invocation = [NSInvocation invocationWithMethodSignature:signature];
    
    if(targetIsClass){
//        [invocation setTarget:NSClassFromString((NSString *)object)];
        [invocation setTarget:object];
    } else {
        [invocation setTarget:object];
    }
    [invocation setSelector:selector];
    
    argumentCount = [arguments count];
    for(i = 0; i < argumentCount; i++){
        id argument = [arguments objectAtIndex:i];
        argumentIndex = i + 2;
        
        // Check if the argument is a special
        if([argument isKindOfClass:[NSString class]]){
            if([argument hasPrefix:@"("]){
                [self handleSimpleArgument:argument forInvokation:invocation atIndex:argumentIndex];
            } else if([argument hasPrefix:@"@"]){
                [self handleSpecialArgument:argument forInvokation:invocation atIndex:argumentIndex];
            }
        } else {
            [invocation setArgument:&argument atIndex:argumentIndex];
        }
    }
#if SHOW_DEBUG_INFO
    NSLog(@"Before invoke: Target %@ with signature %@ (SEL: %s)", [[invocation target] class], [invocation methodSignature], (char *)selector);
#endif
    
    @try{
        [invocation invoke];
    }@catch(NSException *e){
        success = FALSE;
        NSLog(@"Exception: %@",e);
        if(![object respondsToSelector:NSSelectorFromString(methodName)]){
            NSLog(@"Target doesn't respond to %@", methodName);
        }
    }
#if SHOW_DEBUG_INFO
    NSLog(@"After invoke: Target %@ with signature %@ (SEL: %s)", [[invocation target] class], [invocation methodSignature], (char *)selector);
#endif
    return success;
}


#pragma mark Parsing commands
- (BOOL)parseCommandString:(NSString *)commandString{
    NSArray *commandParts = [commandString componentsSeparatedByCharactersInSet:[NSCharacterSet whitespaceAndNewlineCharacterSet]];
    NSString *signal = [commandParts objectAtIndex:0];
    
#if SHOW_DEBUG_INFO
    NSLog(@"Parsing command string '%@'", commandString);
#endif
    
    // Handle the commands
    if([signal hasPrefix:@"@\""]){ // newly created strings
        NSString *objectIdentifier = [commandParts objectAtIndex:1];
        [self setObject:[self transformString:signal] inPoolWithIdentifier:objectIdentifier];
    } else if([signal isEqualToString:@"#"] || [commandString hasPrefix:@">"] || [commandString hasPrefix:@"//"]){ // comments
        say(@"%@\n", commandString);
    } else if([signal isEqualToString:@"new"] || [signal isEqualToString:@"alloc"]){ // object creation
        id object;
        BOOL init = FALSE;
        NSString *newIdentifier;
        NSString *newClassName = [commandParts objectAtIndex:1];
        
        // Check if a third argument is given
        if([commandParts count] > 3){
            if([[commandParts objectAtIndex:3] isEqualToString:@"init"] || 
               [[commandParts objectAtIndex:3] isEqualToString:@"true"] || 
               [[commandParts objectAtIndex:3] isEqualToString:@"TRUE"] || 
               [[commandParts objectAtIndex:3] isEqualToString:@"1"]){
                
#if SHOW_DEBUG_INFO
                NSLog(@"Call init");
#endif
                init = TRUE;
            } else 
            if([[commandParts objectAtIndex:3] isEqualToString:@"noInit"] || 
               [[commandParts objectAtIndex:3] isEqualToString:@"false"] || 
               [[commandParts objectAtIndex:3] isEqualToString:@"FALSE"] || 
               [[commandParts objectAtIndex:3] isEqualToString:@"0"]){
                
#if SHOW_DEBUG_INFO
                NSLog(@"Don't init");
#endif
                init = FALSE;
            }
        }
        
        // If the command is alloc, dont call init
        if([signal isEqualToString:@"alloc"]){
            init = FALSE;
        }
        
        newClassName = [newClassName stringByTrimmingCharactersInSet:[NSCharacterSet whitespaceAndNewlineCharacterSet]];
        if([commandParts count] > 2){
            newIdentifier = [commandParts objectAtIndex:2];
        } else {
            newIdentifier = newClassName;
        }
        
#if SHOW_DEBUG_INFO        
        NSLog(@"New class: '%@'", newClassName);
#endif
        if(init){
            object = [[NSClassFromString(newClassName) alloc] init];
        } else {
            object = [NSClassFromString(newClassName) alloc];
        }
        if(object){
            [self updateObject:object forIdentifier:newIdentifier];
        } else {
            NSLog(@"Couldn't create object ob class %@", newClassName);
        }
#if SHOW_DEBUG_INFO
        NSLog(@"Object: %@", object);
#endif
    } else if([signal isEqualToString:@"printf"]){ // echo
        NSString *format = [commandParts objectAtIndex:1];
        NSObject *object = [self findObjectWithIdentifier:[commandParts objectAtIndex:2]];
        NSLog(format, object);
    } else if([signal isEqualToString:@"echo"]){ // printf
        NSObject *object = [self findObjectWithIdentifier:[commandParts objectAtIndex:1]];
        NSLog(@"echo: %@", object);
    } else if([signal isEqualToString:@"get"]){ // get
        NSObject *object = [self findObjectWithIdentifier:[commandParts objectAtIndex:1]];
        NSString *returnCommand = [NSString stringWithFormat:@"%@", object];
        [self sendObject:returnCommand];
    } else if([signal isEqualToString:@"set"]){ // set
        NSString *objectIdentifier = [commandParts objectAtIndex:1];
        NSObject *newValue = [self findObjectWithIdentifier:[commandParts objectAtIndex:2]];
        [self setObject:newValue inPoolWithIdentifier:objectIdentifier];
    } else if([signal isEqualToString:@"breakpoint"]){ // breakpoint
        NSString *objectIdentifier = [commandParts objectAtIndex:1];
        id object = [self findObjectWithIdentifier:objectIdentifier];
        NSLog(@"objectIdentifier: %@ object: %@",objectIdentifier, object);
    } else if([signal isEqualToString:@"throw"]){ // throw
        id object = nil;
        NSString *name = [commandParts objectAtIndex:1];
        NSString *message = [commandParts objectAtIndex:2];
        NSDictionary *userInfo = nil;
        NSException * exception;
        
        if([commandParts count] > 3){
            object = [self findObjectWithIdentifier:[commandParts objectAtIndex:3]];
            
            if([object isKindOfClass:[NSDictionary class]]){
                userInfo = object;
            } else {
                userInfo = [NSDictionary dictionaryWithObject:object forKey:@"object"];
            }
        }
        exception = [NSException exceptionWithName:name reason:message userInfo:userInfo];
        @throw exception;
    } else if([signal isEqualToString:@"exec"]){ // method execution
        NSMutableArray * shiftedCommandParts = [NSMutableArray arrayWithArray:commandParts];
        [shiftedCommandParts removeObjectAtIndex:0];
        
        [self executeWithCommandParts:[NSArray arrayWithArray:shiftedCommandParts]];
    } else if([self executeWithCommandParts:commandParts]){ // method execution
    } else if([self pluginHandleCommandParts:commandParts]){ // plugin
    } else { // Couldn't parse command
        say(@"Couldn't parse command %@\n", commandString);
        return FALSE;
    }
    return TRUE;
}

- (BOOL)executeWithCommandParts:(NSArray *)commandParts{
    id object;

#if SHOW_DEBUG_INFO
    NSLog(@"Command parts: %@", commandParts);
#endif
    
    if(commandParts.count < 2){
        return FALSE;
    }

    NSString *objectIdentifier =    [commandParts objectAtIndex:0];
    NSString *objectMethod =        [commandParts objectAtIndex:1];
    NSArray * arguments =           [self commandPartsToArguments:commandParts];
    
    targetIsClass = FALSE;
    if([self identifierSignalsClass:objectIdentifier]){
        targetIsClass = TRUE;
        object = objectIdentifier;
#if SHOW_DEBUG_INFO
        NSLog(@"The target is a class (%@)", objectIdentifier);
#endif
    } else if(!(object = [self findObjectWithIdentifier:objectIdentifier])){
#if SHOW_DEBUG_INFO
        NSLog(@"No object for identifier %@", objectIdentifier);
#endif
    }
    
    if(!targetIsClass && [arguments count] == 0){
#if SHOW_DEBUG_INFO
        NSLog(@"Perform selector (without args) %@", objectMethod);
#endif
        [object performSelector:NSSelectorFromString(objectMethod) withObject:nil afterDelay:0.0];
    } else if([self invokeMethodWithName:objectMethod onObject:object withArguments:arguments]){
#if SHOW_DEBUG_INFO
        NSLog(@"Did perform selector (with args) %@", objectMethod);
#endif
    } else {
#if SHOW_DEBUG_INFO
        NSLog(@"Object doesn't respond to %@", objectMethod);
#endif
        return FALSE;
    }
    return TRUE;
}


#pragma mark Plugins
- (BOOL)pluginHandleCommandParts:(NSArray *)commandParts{
    NSString *signal;
    NSString *notificationName;
    NSDictionary *notificationUserInfo;
    NSNotificationCenter *defaultNotificationCenter;
    
    signal = [commandParts objectAtIndex:0];
    
    if(![self hasPluginForCommand:signal]){
        return FALSE;
    }
    
    // Post a notification to allow plugins to handle the command
    defaultNotificationCenter = [NSNotificationCenter defaultCenter];
    notificationUserInfo = [NSDictionary dictionaryWithObjectsAndKeys:
                            commandParts, @"commandParts", 
                            signal, @"command",
                            signal, @"signal",
                            nil];
    notificationName = [NSString stringWithFormat:@"%@%@", PopNotificationNameUnfoundCommandPrefix, signal];
    [defaultNotificationCenter postNotificationName:notificationName object:self userInfo:notificationUserInfo];
    [defaultNotificationCenter postNotificationName:PopNotificationNameUnfoundCommandPrefix object:self userInfo:notificationUserInfo];
    return TRUE;
}
- (BOOL)hasPluginForCommand:(NSString *)command{
    if([pluginPool objectForKey:command]){
        return TRUE;
    }
    return FALSE;
}
- (void)addPlugin:(id)handler selector:(SEL)selector forCommand:(NSString *)command{
    NSString *notificationName = [NSString stringWithFormat:@"%@%@", PopNotificationNameUnfoundCommandPrefix, command];
    [[NSNotificationCenter defaultCenter] addObserver:handler selector:selector name:notificationName object:self];
    [pluginPool setObject:handler forKey:command];
}


#pragma mark Sending commands
- (void)sendCommand:(NSString *)theCommand sender:(id)theSender {
    NSString *commandString;
    NSString *identifier;
    
    identifier = [self identifierForObject:theSender];
    commandString = [NSString stringWithFormat:@"exec %@ %@", identifier, theCommand];
    
#if SHOW_DEBUG_INFO
    NSLog(@"Sending command: '%@'", commandString);
#endif
    [qoqWriteHandle writeData:[commandString dataUsingEncoding:NSUTF8StringEncoding]];
}

- (void)sendCommand:(NSString *)theCommand {
    [self sendCommand:theCommand sender:qoqUnknownSenderArgument];
}

- (void)forwardInvocation:(NSInvocation *)invocation{
    SEL aSelector = [invocation selector];
    NSString *selectorName = NSStringFromSelector(aSelector);
    
    if([selectorName hasSuffix:@"Action:"]){
        [self sendCommand:selectorName];
    } else {
        [self doesNotRecognizeSelector:aSelector];
    }
}

- (void)sendObject:(id)theObject{
    NSString * objectString;
    objectString = [NSString stringWithFormat:@"%@", theObject];
    
#if SHOW_DEBUG_INFO
    NSLog(@"Sending object to QOQ: '%@'", objectString);
#endif
    [qoqWriteHandle writeData:[objectString dataUsingEncoding:NSUTF8StringEncoding]];
}


#pragma mark Task management
- (void)startTask{
    NSProcessInfo * processInfo = [NSProcessInfo processInfo];
    NSArray *args = [processInfo arguments];
    for(NSString *processArgument in args){
        if([processArgument isEqualToString:@"-a"]){
            mode = CDPopModeInteractive;
        }
    }
    
    // Init
    objectPool = [NSMutableDictionary dictionary];
    [objectPool setValue:[NSNull null] forKey:@"nil"];
    
    pluginPool = [NSMutableDictionary dictionary];
    
    // Set the shared instance
    sharedPopServerInstance = self;
    
    qoqUnknownSenderArgument = @"(unknownSender)";
    
    commandDelimiter = [NSCharacterSet characterSetWithCharactersInString:@";\n\r"];
    commandQueue = @"";
    
    // Change to interactive mode if configured
    if(mode == CDPopModeInteractive){
        [self runInteractive];
        return;
    }
    
    // Register for receiving data
    [[NSNotificationCenter defaultCenter] addObserver:self selector:@selector(receiveData:) name:NSFileHandleReadCompletionNotification object:nil];
    
    // Register 
    [[NSNotificationCenter defaultCenter] addObserver:self selector:@selector(stopTask:) name:NSApplicationWillTerminateNotification object:nil];
    
    // Register for task termination
    [[NSNotificationCenter defaultCenter] addObserver:self selector:@selector(taskDidTerminate:) name:NSTaskDidTerminateNotification object:nil];
    
#if SHOW_DEBUG_INFO
    NSLog(@"Call script %@ with arguments: %@", self.taskLaunchPath, self.taskArguments);
#endif
    if(task){
        task = nil;
    }
    task = [[NSTask alloc] init];
    
    
    // Create a named pipe
    FILE *outputFile;
    
    outputFile = fopen([self.qoqPipeName UTF8String], "w+");
    if(outputFile == NULL){
        NSLog(@"Couldn't open the file '%@'", self.qoqPipeName);
        [[NSApplication sharedApplication] terminate:nil];
    }
    qoqWriteHandle = [[NSFileHandle alloc] initWithFileDescriptor: fileno(outputFile) closeOnDealloc: YES];
    
    
    // Creating the pipe for reading from PHP
    NSPipe *outputPipe = [NSPipe pipe];
    popReadHandle = [outputPipe fileHandleForReading];
    
    [task setLaunchPath:self.taskLaunchPath];
    [task setStandardOutput:outputPipe];
    //	[task setStandardInput:inputPipe];
    [task setArguments:self.taskArguments];
    [task setCurrentDirectoryPath:@"~"];
    
    // Set the environment
    NSDictionary *env = [NSDictionary dictionaryWithObjectsAndKeys:
                         [NSNumber numberWithInt:processInfo.processIdentifier], @"popServerPid", 
                         nil];
    [task setEnvironment:env];
    
    @try{
        [task launch];
    } @catch(NSException * e){
        NSLog(@"Exception: %@",e);
    }
    
    [popReadHandle readInBackgroundAndNotify];
}

- (void)receiveData:(NSNotification *)aNotification{
    NSData * data = [[aNotification userInfo] objectForKey:NSFileHandleNotificationDataItem];
    NSString * justReceivedCommand;
    targetIsClass = FALSE;
    
    
    if([data length]){
        justReceivedCommand = [self cleanupString:[[NSString alloc] initWithData:data encoding:NSUTF8StringEncoding]];
        commandQueue = [commandQueue stringByAppendingString:[justReceivedCommand stringByReplacingOccurrencesOfString:@"> " withString:@""]];
        
        // Check if there is a delimiter in the command queue
        NSRange commandDelimiterRange = [commandQueue rangeOfCharacterFromSet:commandDelimiter];
        if(commandDelimiterRange.location != NSNotFound){
            // Handle the commands in the queue
            NSArray * commandLines = [commandQueue componentsSeparatedByCharactersInSet:commandDelimiter];
            for(NSString * commandLineString in commandLines){
                if([commandLineString length]){
                    commandLineString = [self prepareCommandString:commandLineString];
                    [self parseCommandString:commandLineString];
                }
            }
            commandQueue = @"";
        } else {
            // Print the input to show what is typed
            say(justReceivedCommand);
            fflush(stdout);
        }
        
    }

    if([task isRunning]){
		[popReadHandle readInBackgroundAndNotify];
	}
}

- (void)taskDidTerminate:(NSNotification *)notif{
	int status = [task terminationStatus];
    
#if SHOW_DEBUG_INFO
	if(status == 0){
		NSLog(@"Task succeeded.");
	} else {
		NSLog(@"Task failed.");
	}
#endif
    if(status){
        
    }
}

- (void)stopTask{
    if([task isRunning]){
        [task terminate];
    }
}


#pragma mark Preparing raw commands
- (NSString *)prepareRawCommandInput:(NSString *)commandInput{
    return [self cleanupString:commandInput];
}

- (NSString *)prepareCommandString:(NSString *)commandString {
    return [[commandString stringByTrimmingCharactersInSet:[NSCharacterSet controlCharacterSet]] stringByTrimmingCharactersInSet:[NSCharacterSet whitespaceAndNewlineCharacterSet]];
}

-(NSString *)cleanupString:(NSString *)input{
    static NSRegularExpression *regex;
    if(!regex){
        NSError * error = NULL;
        regex = [NSRegularExpression regularExpressionWithPattern:@"[^0-9a-z|;|,|\\-|!|$|%|=|*|+|\\.|:| |_|@|&|\"|'|´|`|<|>|#|/|\\(|\\)|\n|\\\\|\\|]"
                                                          options:NSRegularExpressionCaseInsensitive
                                                            error:&error];
        if(error){
            NSLog(@"Error while creating a regular expression. %@", error);
            @throw error;
        }
    }
    NSString *modifiedString = [regex stringByReplacingMatchesInString:input
                                                               options:0
                                                                 range:NSMakeRange(0, [input length])
                                                          withTemplate:@""];
    return modifiedString;
}


#pragma mark Interactive
-(BOOL)runInteractive{
    NSTimeInterval timeInterval = (NSTimeInterval) 0.1;
    uint64_t timeInterval_gcd;
    timeInterval_gcd = timeInterval * NSEC_PER_SEC;
    
    // Info: http://libdispatch.macosforge.org/trac/wiki/tutorial#Respondingtoevents:Sources
    dispatch_queue_t queue	= dispatch_get_global_queue(DISPATCH_QUEUE_PRIORITY_DEFAULT, 0);
    
    dispatch_source_t source = dispatch_source_create(DISPATCH_SOURCE_TYPE_TIMER, 0, 0, queue);
    if(!source){
        @throw [NSException exceptionWithName:@"No source" reason:@"Source couldn't be created" userInfo:nil];
    }
    dispatch_source_set_timer(source, 0, timeInterval_gcd, 0);
    
    void (^runInteractiveBlock)(void);
    runInteractiveBlock = ^(void) {
        [self runInteractiveLoop:nil];
    };
    
    
    dispatch_source_set_event_handler(source, runInteractiveBlock);
    dispatch_resume(source);
    
    
    say(@"POP Interactive Console (Version 0.1.0)\n\
Use \"help\", \"copyright\" or \"license\" for more information.\n");
    return TRUE;
}

-(void)runInteractiveLoop:(NSTimer *)aTimer{
    char buffer[8192];
    NSString *commandString;
    NSArray * commandLines;
    static NSCharacterSet *newlineCharacterSet;
    
    if(!newlineCharacterSet){
        newlineCharacterSet = [NSCharacterSet whitespaceAndNewlineCharacterSet];
    }
    
    say(@"> ");
    
    fgets(buffer, 8192, stdin);
    commandString = [self cleanupString:[NSString stringWithCString:buffer encoding:NSUTF8StringEncoding]];
    
    if(commandString.length == 0){
        // Do nothing
    } else if([commandString isEqualToString:@"help"]){
        say(@"POP Help:\n Creating:    'new NSWindow variableName [noInit]'   Creates a new object (Set 'noInit' if you only wont to alloc)\n Execution:   'exec variableName method' or 'exec variableName method arg0 ... argN'    Executes the method on the object\n printf:      'printf format variableName'           Prints a formatted string [ = printf(format, variableName) ]\n echo:        'echo variableName'                    Logs the value from variableName [ = NSLog ]\n get:         'get variableName'                     Currently the same as \"echo\"\n set:         'set variableName newValue'            Sets the newValue for the variableName\n");     
    } else if([commandString isEqualToString:@"copyright"]){
        say(@"(c) 2012 Corn Daniel\n");
    } else if([commandString isEqualToString:@"license"]){
        say(@"Copyright (c) 2012 Daniel Corn\n\nPermission is hereby granted, free of charge, to any person obtaining a \ncopy of this software and associated documentation files (the \"Software\"), \nto deal in the Software without restriction, including without limitation \nthe rights to use, copy, modify, merge, publish, distribute, sublicense, \nand/or sell copies of the Software, and to permit persons to whom the \nSoftware is furnished to do so, subject to the following conditions:\n\nThe above copyright notice and this permission notice shall be included in \nall copies or substantial portions of the Software.\n\nTHE SOFTWARE IS PROVIDED \"AS IS\", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR \nIMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, \nFITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL \nTHE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER \nLIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING \nFROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER \nDEALINGS IN THE SOFTWARE.\n");
    } else if([commandString isEqualToString:@"exit"]){
        say(@"Goodbye\n");
        [[NSApplication sharedApplication] terminate:self];
    } else {
        commandLines = [commandString componentsSeparatedByCharactersInSet:commandDelimiter];
        for(NSString * commandLineString in commandLines){
            commandLineString = [commandLineString stringByTrimmingCharactersInSet:newlineCharacterSet];
            if([commandLineString length]){
                [self parseCommandString:commandLineString];
            }
        }
    }
}


#pragma mark Initialization
- (NSString *)taskScriptPath{
    if(!taskScriptPath){
        taskScriptPath = [[NSBundle mainBundle] pathForResource:@"run" ofType:@"php" inDirectory:@"qoq"];
    }
    return taskScriptPath;
}

- (NSString *)taskLaunchPath{
    if(!taskLaunchPath){
        taskLaunchPath = @"/usr/bin/env";
    }
    return taskLaunchPath;
}

- (NSString *)qoqPipeName{
    if(!qoqPipeName){
        qoqPipeName = [NSString stringWithFormat:@"%s", kCDNamedPipe];
    }
    return qoqPipeName;
}

- (NSMutableArray *)taskArguments{
    if(!taskArguments){
        taskArguments = [NSMutableArray arrayWithObjects:@"php", self.taskScriptPath, nil];
    }
    return taskArguments;
}

-(id)init{
    self = [super init];
    if(self){
        [self startTask];
    }
    return self;
}

- (void)dealloc{
    [super dealloc];
    [self stopTask];
}

- (void)finalize{
    [super finalize];
    [self stopTask];
}
- (void)stopTask:(NSNotification *)notif{
    [self stopTask];
}


#pragma mark Shared instance
+ (PopServer *)sharedInstance{
    if(sharedPopServerInstance == nil){
        sharedPopServerInstance = [[PopServer alloc] init];
    }
    return sharedPopServerInstance;
}
@end
