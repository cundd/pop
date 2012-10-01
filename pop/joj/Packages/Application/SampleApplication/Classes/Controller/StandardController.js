/*
 * @license
 */

var Backbone	= require(__dirname + '/../../../../Framework/Joj/Classes/node_modules/backbone/backbone.js'),
	Joj			= require(__dirname + '/../../../../Framework/Joj/Classes/Runtime.js');

/**
 * An abstract class to represent basic values.
 * @class SampleApplication.Controller.StandardController
 */
exports.StandardController = Backbone.Model.extend({
	applicationDidFinishLaunching: function () {
		console.log('hallo');
	}
});