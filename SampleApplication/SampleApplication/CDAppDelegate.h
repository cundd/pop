//
//  CDAppDelegate.h
//  SampleApplication
//
//  Created by Daniel Corn on 23.06.12.
//  Copyright (c) 2012 cundd. All rights reserved.
//

#import <Cocoa/Cocoa.h>
#import "PopServer.h"

@interface CDAppDelegate : PopServer <NSApplicationDelegate> {
}

@property (assign) IBOutlet NSWindow *window;

@end