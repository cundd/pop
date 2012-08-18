//
//  main.m
//  pop
//
//  Created by Daniel Corn on 18.08.12.
//  Copyright (c) 2012 cundd. All rights reserved.
//

#import <Foundation/Foundation.h>
#import "PoiAppDelegate.h"

NSString *NSApplicationWillTerminateNotification = @"NSApplicationWillTerminateNotification";

int main(int argc, const char * argv[])
{
    PoiAppDelegate * delegate;
    @autoreleasepool {
        delegate = [[PoiAppDelegate alloc] init];
    }
    return 0;
}

