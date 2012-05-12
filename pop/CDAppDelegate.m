//
//  CDAppDelegate.m
//  pop
//
//  Created by Daniel Corn on 02.05.12.
//  Copyright (c) 2012 cundd. All rights reserved.
//

#import "CDAppDelegate.h"
#import "NSWindow+CDWindow.m"

@implementation CDAppDelegate

@synthesize window = _window;
@synthesize objectPool;


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
        NSLog(@"arg: '%@'", argumentIdentifier);
        
        if([argumentIdentifier hasPrefix:@"@"] || [argumentIdentifier hasPrefix:@"("]){ // Check if it is a special or simple type argument
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
    NSLog(@"Simple arg: %@",argument);
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
        NSLog(@"%lu",value);
        [invocation setArgument:&value atIndex:index];
    } else if([argument hasPrefix:@"(uinteger)"]){
        NSUInteger value = [[argument substringFromIndex:10] intValue];
        [invocation setArgument:&value atIndex:index];
    }
}

- (NSString *)transformString:(NSString *)inputString{
    return [inputString stringByReplacingOccurrencesOfString:@"&nbsp%" withString:@" "];
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
    } else if([argument hasPrefix:@"@\""] || [argument hasPrefix:@"@'"]){
        NSUInteger length = [argument length] - 2 - 1;
        argument = [argument substringWithRange:NSMakeRange(2, length)];
        
        argument = [self transformString:argument];
        
        [invocation setArgument:&argument atIndex:index];
    }
}

- (BOOL)invokeMethodWithName:(NSString *)methodName onObject:(NSObject *)object withArguments:(NSArray *)arguments{
    BOOL success = TRUE;
    NSUInteger argumentCount, argumentIndex, i;
    NSInvocation *invocation;
    NSMethodSignature *signature;
    SEL selector;
    
    // First attempt to create the method signature with the provided selector.
    [methodName retain];
    selector = NSSelectorFromString(methodName);
    
    if(targetIsClass){
        Class targetClass = NSClassFromString((NSString *)object);
        signature = [targetClass instanceMethodSignatureForSelector:selector];
    } else {
        signature = [object methodSignatureForSelector:selector];
    }
    if (!signature) {
        NSLog(@"NSObject: Method signature could not be created.");
        return FALSE;
    }
    [signature retain];
    
    NSLog(@"Args: %@", arguments);
    
    // Next we create the invocation that will actually call the required selector.
    invocation = [NSInvocation invocationWithMethodSignature:signature];
    
    if(targetIsClass){
        [invocation setTarget:NSClassFromString((NSString *)object)];
    } else {
        [invocation setTarget:object];
    }
    [invocation setSelector:selector];
    
    argumentCount = [arguments count];
    for(i = 0; i < argumentCount; i++){
        id argument = [arguments objectAtIndex:i];
        argumentIndex = i + 2;
        
        NSLog(@"Arg: %@ for index %u", argument, (uint)argumentIndex);
        
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
    [invocation retainArguments];
    
    NSMethodSignature *sigi = [invocation methodSignature];
    if(sigi != signature){
        NSLog(@"Signature isn't the same");
    }
    NSLog(@"Before invoke: Target %@ with signature %@ (SEL: %s)", [[invocation target] class], [invocation methodSignature], selector);
//    [invocation performSelector:@selector(invoke)];
    
    @try{
        [invocation invoke];
    }@catch(NSException *e){
        success = FALSE;
        NSLog(@"Exception: %@",e);
        if(![object respondsToSelector:NSSelectorFromString(methodName)]){
            NSLog(@"Target doesn't respond to %@", methodName);
        }
    }
    
    NSLog(@"After invoke: Target %@ with signature %@ (SEL: %s)", [[invocation target] class], [invocation methodSignature], selector);
    
    if(![invocation.target isEqualTo:object]){
        NSLog(@"The target is not the object");
    }
//  [invocation performSelectorOnMainThread:@selector(invoke) withObject:nil waitUntilDone:wait];
    return success;
}

- (id)findObjectInPoolWithIdentifier:(NSString *)identifier{
    return [objectPool objectForKey:identifier];
}

- (void)setObject:(id)object inPoolWithIdentifier:(NSString *)identifier{
    [objectPool setObject:object forKey:identifier];
}

- (void)updateObject:(id)object forIdentifier:(NSString *)identifier{
    [identifier retain];
    // Check if the identifier belongs to a property
    if([self respondsToSelector:NSSelectorFromString(identifier)]){
        [self setValue:object forKeyPath:identifier];
    } else {
        [self setObject:object inPoolWithIdentifier:identifier];
    }
    [identifier release];
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

- (id)findObjectWithIdentifier:(NSString *)identifier{
    id object;
    
    if([self identifierSignalsClass:identifier]){
        object = identifier;
    } else {
        NSLog(@"%@ %@", identifier, objectPool);
        object = [self findObjectInPoolWithIdentifier:identifier];
    }
    if(!object){
        if([self isKVCCompliantForKey:identifier]){
            object = [self valueForKeyPath:identifier];
        }
    }
    return object;
}

- (BOOL)isKVCCompliantForKey:(NSString *)keyPath{
    NSArray * keyPathArray = [keyPath componentsSeparatedByString:@"."];
    NSString * firstKey = [keyPathArray objectAtIndex:0];
    if([self respondsToSelector:NSSelectorFromString(firstKey)]){
        return TRUE;
    }
    return FALSE;
}

- (BOOL)parseCommandString:(NSString *)commandString{
    NSArray *commandParts = [commandString componentsSeparatedByCharactersInSet:[NSCharacterSet whitespaceAndNewlineCharacterSet]];
    NSString *command = [commandParts objectAtIndex:0];
    
    NSLog(@"Parsing command string '%@'", commandString);
    
    // Handle the commands
    if([command isEqualToString:@"#"] || [commandString hasPrefix:@">"]){ // comments
        NSLog(@"readline");
    } else if([command isEqualToString:@"new"]){ // object creation
        id object;
        BOOL init = TRUE;
        NSString *newIdentifier;
        NSString *newClassName = [commandParts objectAtIndex:1];
        
        // Check if a third argument is given
        if([commandParts count] > 3){
            if([[commandParts objectAtIndex:3] isEqualToString:@"noInit"] || 
               [[commandParts objectAtIndex:3] isEqualToString:@"true"] || 
               [[commandParts objectAtIndex:3] isEqualToString:@"TRUE"] || 
               [[commandParts objectAtIndex:3] isEqualToString:@"1"]){
                init = FALSE;
            }
        }
        newClassName = [newClassName stringByTrimmingCharactersInSet:[NSCharacterSet whitespaceAndNewlineCharacterSet]];
        
        if([commandParts count] > 2){
            newIdentifier = [commandParts objectAtIndex:2];
        } else {
            newIdentifier = newClassName;
        }
        
        
        NSLog(@"New class: '%@'", newClassName);
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
        NSLog(@"Object: %@", object);
    } else if([command isEqualToString:@"printf"]){ // echo
        NSString *format = [commandParts objectAtIndex:1];
        NSObject *object = [self findObjectWithIdentifier:[commandParts objectAtIndex:2]];
        NSLog(format, object);
    } else if([command isEqualToString:@"echo"]){ // printf
        NSObject *object = [self findObjectWithIdentifier:[commandParts objectAtIndex:1]];
        NSLog(@"%@", object);
    } else if([command isEqualToString:@"get"]){ // get
        NSObject *object = [self findObjectWithIdentifier:[commandParts objectAtIndex:1]];
        NSLog(@"%@", object);
    } else if([command isEqualToString:@"set"]){ // set
        NSString *objectIdentifier = [[commandParts objectAtIndex:1] retain];
        NSObject *newValue = [self findObjectWithIdentifier:[commandParts objectAtIndex:2]];
        [self setObject:newValue inPoolWithIdentifier:objectIdentifier];
        
//    } else if([command isEqualToString:@"breakpoint"]){ // breakpoint
//        NSString *objectIdentifier = [commandParts objectAtIndex:1];
//        NSWindow * window = (NSWindow *)[self findObjectWithIdentifier:objectIdentifier];
//        [window makeKeyAndOrderFront:nil];
//        
//        NSWindow * newWin = [[NSWindow alloc] initWithContentRect: NSMakeRect(300, 300, 200, 100)
//                                             styleMask: (NSTitledWindowMask |
//                                                         NSMiniaturizableWindowMask |
//                                                         NSResizableWindowMask)
//                                               backing: NSBackingStoreBuffered
//                                                 defer: YES];
//        
//        NSLog(@"CuCu %u", (NSTitledWindowMask |
//                      NSMiniaturizableWindowMask |
//                      NSResizableWindowMask));
//        [newWin makeKeyAndOrderFront:nil];
//        [newWin setTitle: @"Hello World"];
//        
    } else if([command isEqualToString:@"breakpoint"]){ // breakpoint
        NSString *objectIdentifier = [commandParts objectAtIndex:1];
        id object = [self findObjectWithIdentifier:objectIdentifier];
        NSLog(@"objectIdentifier: %@ object: %@",objectIdentifier, object);
    } else if([command isEqualToString:@"throw"]){ // throw
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
    } else if([command isEqualToString:@"exec"]){ // method execution
        NSMutableArray * shiftedCommandParts = [NSMutableArray arrayWithArray:commandParts];
        [shiftedCommandParts removeObjectAtIndex:0];
        
        [self executeWithCommandParts:[NSArray arrayWithArray:shiftedCommandParts]];
    } else if([self executeWithCommandParts:commandParts]){ // method execution
    } else { // Couldn't parse command
        
        NSLog(@"Couldn't parse command %@", commandString);
        return FALSE;
    }
    return TRUE;
}

- (BOOL)executeWithCommandParts:(NSArray *)commandParts{
    id object;
    
    NSString *objectIdentifier =    [[commandParts objectAtIndex:0] retain];
    NSString *objectMethod =        [[commandParts objectAtIndex:1] retain];
    NSArray * arguments =           [self commandPartsToArguments:commandParts];
    
    NSLog(@"Command parts: %@", commandParts);
    
    if([self identifierSignalsClass:objectIdentifier]){
        targetIsClass = TRUE;
        object = objectIdentifier;
        NSLog(@"Got class: %@", objectIdentifier);
    } else {
        object = [self findObjectWithIdentifier:objectIdentifier];
        NSLog(@"Got object: %@ for identifier '%@'", object, objectIdentifier);
    }
    
    if([arguments count] == 0){
        NSLog(@"Perform selector (without args) %@", objectMethod);
        [object performSelector:NSSelectorFromString(objectMethod) withObject:nil afterDelay:0.0];
    } else if([self invokeMethodWithName:objectMethod onObject:object withArguments:arguments]){
        NSLog(@"Did perform selector (with args) %@", objectMethod);
    } else {
        NSLog(@"Object doesn't respond to %@", objectMethod);
    }
    
    [objectIdentifier release];
    [objectMethod release];
    
    return TRUE;
}

- (void)receiveData:(NSNotification *)aNotification{
    static NSCharacterSet * illegalCharacters;
    static NSCharacterSet * controlCharacters;
    NSData * data = [[aNotification userInfo] objectForKey:NSFileHandleNotificationDataItem];
    NSString * justReceivedCommand;
    
    targetIsClass = FALSE;
    
    if(!illegalCharacters){
        illegalCharacters = [NSCharacterSet illegalCharacterSet];
        controlCharacters = [NSCharacterSet controlCharacterSet];
    }
    
    if([data length]){
        justReceivedCommand = [[[NSString alloc] initWithData:data encoding:NSUTF8StringEncoding] stringByTrimmingCharactersInSet:illegalCharacters];
        
        [commandQueue release];
        commandQueue = [commandQueue stringByAppendingString:[justReceivedCommand stringByReplacingOccurrencesOfString:@"> " withString:@""]];
        [commandQueue retain];
        
        // Check if there is a delimiter in the command queue
        NSRange commandDelimiterRange = [commandQueue rangeOfCharacterFromSet:commandDelimiter];
        if(commandDelimiterRange.location != NSNotFound){
            // Handle the commands in the queue
//            [commandQueue release];
            commandQueue = [commandQueue stringByTrimmingCharactersInSet:illegalCharacters];
//            [commandQueue retain];
            NSArray * commandLines = [commandQueue componentsSeparatedByCharactersInSet:commandDelimiter];
            for(NSString * commandLineString in commandLines){
                if([commandLineString length]){
                    commandLineString = [commandLineString stringByTrimmingCharactersInSet:controlCharacters];
                    [self parseCommandString:commandLineString];
                }
            }
            [commandQueue release];
            commandQueue = @"";
        } else {
            // Print the input to show what is typed
//            NSLog(@"%@", justReceivedCommand);
            printf("%s", [justReceivedCommand UTF8String]);
            fflush(stdout);
        }
        
    }

    if([task isRunning]){
		[readHandle readInBackgroundAndNotify];
	}
}

-(void)taskDidTerminate:(NSNotification *)notif{
	int status = [task terminationStatus];
	if(status == 0){
		NSLog(@"Task succeeded.");
	} else {
		NSLog(@"Task failed.");
	}
}

- (void)applicationDidFinishLaunching:(NSNotification *)aNotification
{
    NSString * launchPath;
	NSMutableArray * arguments;
	
    if(!scriptPath){
        scriptPath = @"/Volumes/Daten HD/Users/daniel/Sites/Resources/pop/pop/run.php";
    }
    arguments = [NSArray arrayWithObjects:@"php", scriptPath, nil];
    launchPath = @"/usr/bin/env";
    
    // Init
    objectPool = [NSMutableDictionary dictionary];
    [objectPool retain];
    [objectPool setValue:[NSNull null] forKey:@"nil"];
    
    commandDelimiter = [[NSCharacterSet characterSetWithCharactersInString:@";\n\r"] retain];
    commandQueue = @"";
    
    
    [[NSNotificationCenter defaultCenter] addObserver:self selector:@selector(receiveData:) name:NSFileHandleReadCompletionNotification object:nil];
    //[[NSNotificationCenter defaultCenter] addObserver:self selector:@selector(receiveData:) name:NSFileHandleReadToEndOfFileCompletionNotification object:nil];
    
    // Register for task termination
	[[NSNotificationCenter defaultCenter] addObserver:self selector:@selector(taskDidTerminate:) name:NSTaskDidTerminateNotification object:nil];
    
	NSLog(@"Call script %@ with arguments: %@", launchPath, arguments);
	if(task){
		task = nil;
	}
	task = [[NSTask alloc] init];
	
	NSPipe *outputPipe = [NSPipe pipe];
	readHandle = [outputPipe fileHandleForReading];
    NSLog(@"Read using handle: %i", [readHandle fileDescriptor]);
    
	[task setLaunchPath:launchPath];
	[task setStandardOutput:outputPipe];
    //	[task setStandardInput:inputPipe];
	[task setArguments:arguments];
	[task setCurrentDirectoryPath:@"~"];
	
    //	NSLog(@"%@",[task environment]);
	[task setEnvironment:[NSDictionary dictionaryWithObjectsAndKeys:@"/tmp/launch-ooAfUm/Listeners", @"SSH_AUTH_SOCK", nil]];
    //	NSLog(@"%@",[task environment]);
	
	@try{
		[task launch];
	} @catch(NSException * e){
		NSLog(@"Exc: %@",e);
//		NSString * additionalOutputString = [NSString stringWithFormat:@"%@: %@\n",[e name],[e reason]];
	}
	
//	[readHandle readToEndOfFileInBackgroundAndNotify];
    [readHandle readInBackgroundAndNotify];
    
    // id object = [[NSClassFromString(@"NSObject") alloc] init];
}

@end
