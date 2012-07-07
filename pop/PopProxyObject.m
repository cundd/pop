//
//  PopProxyObject.m
//  pop
//
//  Created by Daniel Corn on 23.06.12.
//  Copyright (c) 2012 cundd. All rights reserved.
//

#import "PopProxyObject.h"

@implementation PopProxyObject
@synthesize value;

- (void)forwardInvocation:(NSInvocation *)anInvocation
{
    self.value;
}

@end
