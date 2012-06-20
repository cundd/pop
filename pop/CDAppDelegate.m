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

#import "CDAppDelegate.h"

@implementation CDAppDelegate

@synthesize textfield, secondWindow;
@synthesize window = _window;

- (void)applicationWillTerminate:(NSNotification *)notification{
}

-(void)handleNSBundle:(NSNotification*)notif{
    NSLog(@"Plugin: %@", notif.userInfo);
    //NSString * signal = [notif.userInfo objectForKey:@"signal"]; // This will be "NSBundle"
    NSArray * commandParts = [notif.userInfo objectForKey:@"commandParts"];
    NSString * method = [commandParts objectAtIndex:1];
    
    if([method isEqualToString:@"loadNibNamed:owner:"]){
        NSString * bundleName = [commandParts objectAtIndex:2];
        NSObject * owner = [commandParts objectAtIndex:3];
        [NSBundle loadNibNamed:bundleName owner:owner];
    }
}

- (void)applicationDidFinishLaunching:(NSNotification *)aNotification {
    [self addPlugin:self selector:@selector(handleNSBundle:) forCommand:@"NSBundle"];
}

- (IBAction)loadNibAction:(id)sender{
    NSString *command = @"loadNibAction:";
    [self sendCommand:command sender:sender];
}
@end
