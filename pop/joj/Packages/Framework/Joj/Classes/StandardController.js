/*
 * @license
 */

var Backbone	= require('Backbone'),
	Joj			= require('./Joj.js');

/**
 * An abstract class to represent basic values.
 * @class SampleApplication.Controller.StandardController
 */
exports.StandardController = Backbone.Model.extend({
	window: null,

	applicationDidFinishLaunching: function () {
		// console.log('new NSWindow myWin;');

		this.window = new Joj.ProxyObject({className: 'NSWindow'});
		Joj.Runtime.pd('The window should now exist');
		//                                                                @NSMakeRect(100,100,300,400)      uint(13)     uint(2)    (int)1
		this.window.call('initWithContentRect:styleMask:backing:defer:', '@@NSMakeRect(100,100,300,400)', '@(uint)13', '@(uint)2', '@(int)1');
		this.window.call('makeKeyAndOrderFront:', Joj.Nil.sharedInstance());
		Joj.Runtime.sendCommand('echo ' + this.window);

		var defaultWindow = new Joj.ProxyObject({className: 'NSWindow', id: 'window', createPopObject: false});
		defaultWindow.call('close');
		//Joj.Runtime.sendCommand('break ' + this.window);

	},

	setCommandParts: function (cmd) {
		console.log('# CMD:', cmd);
	}
});