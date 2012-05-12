//
//  NSWindow+CDWindow.h
//  pop
//
//  Created by Daniel Corn on 05.05.12.
//  Copyright (c) 2012 cundd. All rights reserved.
//

#import <Cocoa/Cocoa.h>

@interface NSWindow (CDWindow)
+ (id)windowWithContentRect:(NSRect)contentRect styleMask:(NSUInteger)windowStyle backing:(NSBackingStoreType)bufferingType defer:(BOOL)deferCreation;
- (void)doesNotRecognizeSelector:(SEL)aSelector;
@end
