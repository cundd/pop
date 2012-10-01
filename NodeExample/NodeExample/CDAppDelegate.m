//
//  CDAppDelegate.m
//  NodeExample
//
//  Created by Daniel Corn on 17.09.12.
//  Copyright (c) 2012 Daniel Corn. All rights reserved.
//

#import "CDAppDelegate.h"

@implementation CDAppDelegate

- (void)applicationDidFinishLaunching:(NSNotification *)aNotification{
    // Insert code here to initialize your application
}

- (NSString *)taskScriptPath{
    if(!taskScriptPath){
        taskScriptPath = [[NSBundle mainBundle] pathForResource:@"run" ofType:@"js" inDirectory:@"joj"];
    }
    return taskScriptPath;
}

- (NSMutableArray *)taskArguments{
    if(!taskArguments){
        taskArguments = [NSMutableArray arrayWithObjects:@"/usr/local/bin/node", self.taskScriptPath, nil];

    }
    return taskArguments;
}

@end
