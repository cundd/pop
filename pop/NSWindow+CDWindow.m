//
//  NSWindow+CDWindow.m
//  pop
//
//  Created by Daniel Corn on 05.05.12.
//  Copyright (c) 2012 cundd. All rights reserved.
//

#import "NSWindow+CDWindow.h"

@implementation NSWindow (CDWindow)
+ (id)windowWithContentRect:(NSRect)contentRect styleMask:(NSUInteger)windowStyle backing:(NSBackingStoreType)bufferingType defer:(BOOL)deferCreation {
    return [[self alloc] initWithContentRect:contentRect styleMask:windowStyle backing:bufferingType defer:deferCreation];
}
/*
- (void)doesNotRecognizeSelector:(SEL)aSelector{
    NSLog(@"Sel %s", aSelector);
}

+ (BOOL)resolveClassMethod:(SEL)name{
    NSLog(@"Baby: %s", name);
    return TRUE;
}
 // */
@end
