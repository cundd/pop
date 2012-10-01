#!/usr/bin/env node

var Qoq = Qoq || {};

Qoq.ProxyObject = {
	_uuid: '',

	create: function (className, uuid) {
		var newInstance, uuidSuffix;
		newInstance = Object.create(this);
		if (!uuid) {
			uuidSuffix = new Date().getTime();
			uuid = 'inst-' + className + '-' + uuidSuffix;
		}
		
		newInstance._uuid = uuid;

		if (className) {
			console.log('new ' + className + ' ' + uuid);
		}
		return newInstance;
	},

	get: function (name) {
		if (this[name]) {
			return this[name];
		}
		return null;
	},

	convertValuesToArgumentsArray: function (rawArguments) {
		var i = 1, // 0 is the method name
			argument,
			prerparedArgument,
			preparedArgumentCollection = [],
			length = rawArguments.length;

		for (; i < length; i++) {
			argument = rawArguments[i];
			switch (typeof argument) {
				case 'boolean':
					prerparedArgument = '(bool)' + argument;
					break;

				case 'number':
					prerparedArgument = '(float)' + argument;
					break;

				case 'string':
					if (argument === 'nil') {
						prerparedArgument = 'nil';
					} else if (argument.charAt(0) === '@') {
						prerparedArgument = argument.substr(1);
					} else {
						prerparedArgument = '@"' + (argument.replace(' ', '&_')) + '"';
					}
					break;

				case 'null':
				default:
					prerparedArgument = 'nil';
					break;
			}
			preparedArgumentCollection.push(prerparedArgument);
		}
		return preparedArgumentCollection;
	},


	call: function (methodName) {
		console.log(this._uuid + ' ' + methodName + ' ' + this.convertValuesToArgumentsArray(arguments).join(' '));
	}
}

setTimeout(function () {

var i;
console.log('# Hello');

//while(i++ < 10000) {}; i = 0;
console.log('window setTitle: @"New&_title&_of&_the&_NodeExample&_window";');
//while(i++ < 10000) {}; i = 0;

console.log("# ende");

//while(i++ < 10000) {}; i = 0;
var myWin;
// console.log('new NSWindow myWin;');
myWin = Qoq.ProxyObject.create('NSWindow');
console.log('# The window should now exist');
//                                                          @NSMakeRect(100,100,300,400)      uint(13)     uint(2)    (int)1
myWin.call('initWithContentRect:styleMask:backing:defer:', '@@NSMakeRect(100,100,300,400)', '@(uint)13', '@(uint)2', '@(int)1')
console.log('echo ' + myWin._uuid);

myWin.call('makeKeyAndOrderFront:', 'nil');
console.log('# Ende;');

           setTimeout(function(){console.log('# Ende;');}, 10);

}, 2000);