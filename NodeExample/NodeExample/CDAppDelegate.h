//
//  CDAppDelegate.h
//  NodeExample
//
//  Created by Daniel Corn on 17.09.12.
//  Copyright (c) 2012 Daniel Corn. All rights reserved.
//

#import <Cocoa/Cocoa.h>
#import "PopServer.h"

@interface CDAppDelegate : PopServer <NSApplicationDelegate>

@property (assign) IBOutlet NSWindow *window;

@end
