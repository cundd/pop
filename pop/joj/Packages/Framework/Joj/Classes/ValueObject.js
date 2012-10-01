/*
 * @license
 */

var Backbone	= require('Backbone'),
	Joj			= require('./Runtime');

/**
 * An abstract class to represent basic values.
 */
var ValueObject = Backbone.Model.extend({
	/**
	 * The represented value.
	 * @var mixed
	 */
	value: null,

	/**
	 * Returns the type prefix.
	 * @return string
	 */
	getTypePrefix: function () {
		return '';
	},

	/**
	 * Returns the string representation of the value and the argument that will
	 * be sent if the object is passed to POP.
	 * @return string
	 */
	toString: function () {
		if (this.get('value')) {
			return this.getTypePrefix() + this.get('value');
		}
		return '';
	}
});
exports.ValueObject = ValueObject;


/**
 * The int wrapper object.
 */
var Int = ValueObject.extend({
	/**
	 * Returns the type prefix.
	 * @return string
	 */
	getTypePrefix: function () {
		return '(int)';
	}
});
exports.Int = Int;


/**
 * The uint wrapper object.
 */
var Uint = ValueObject.extend({
	/**
	 * Returns the type prefix.
	 * @return string
	 */
	getTypePrefix: function () {
		return '(uint)';
	}
});
exports.Uint = Uint;


/**
 * The selector wrapper object.
 */
var Selector = ValueObject.extend({
	/**
	 * Returns the type prefix.
	 * @return string
	 */
	getTypePrefix: function () {
		return '(SEL)';
	}
});
exports.Selector = Selector;


/**
 * The selector wrapper object.
 */
var SEL = Selector.extend({
	/**
	 * Returns the type prefix.
	 * @return string
	 */
	getTypePrefix: function () {
		return '(SEL)';
	}
});
exports.SEL = SEL;


/**
 * The string wrapper object.
 */
var ObjCString = ValueObject.extend({
	/**
	 * Returns the string representation of the value and the argument that will
	 * be sent if the object is passed to POP.
	 * @return string
	 */
	toString: function () {
		if (this.get('value')) {
			return this.getTypePrefix() + this.get('value');
		}
		return '';
	}
});
exports.String = String;


/**
 * The point wrapper object.
 */
var Point = Backbone.Model.extend({
	/**
	 * The X coordinate.
	 * @var float
	 */
	x: 0.0,
	
	/**
	 * The Y coordinate.
	 * @var float
	 */
	y: 0.0,

	/**
	 * Returns the string representation of the value and the argument that will
	 * be sent if the object is passed to POP.
	 * @return string
	 */
	toString: function () {
		var x = this.get('x'),
			y = this.get('y');
		return '@NSMakePoint(' + x + ',' + y + ')';
	}
});
exports.Point = Point;


/**
 * The size wrapper object.
 */
var Size = Backbone.Model.extend({
	/**
	 * The width.
	 * @var float
	 */
	width: 0.0,
	
	/**
	 * The height.
	 * @var float
	 */
	height: 0.0,

	/**
	 * Returns the string representation of the value and the argument that will
	 * be sent if the object is passed to POP.
	 * @return string
	 */
	toString: function () {
		var width = this.get('width'),
			height = this.get('height');
		return '@NSMakeSize(' + width + ',' + height + ')';
	}
});
exports.Size = Size;


/**
 * The rect wrapper object.
 */
var Rect = Backbone.Model.extend({
	/**
	 * The X coordinate.
	 * @var float
	 */
	x: 0.0,
	
	/**
	 * The Y coordinate.
	 * @var float
	 */
	y: 0.0,
	
	/**
	 * The width.
	 * @var float
	 */
	width: 0.0,
	
	/**
	 * The height.
	 * @var float
	 */
	height: 0.0,

	/**
	 * Returns the string representation of the value and the argument that will
	 * be sent if the object is passed to POP.
	 * @return string
	 */
	toString: function () {
		return '@NSMakeRect(' + this.get('x') + ',' + this.get('y') + ',' + this.get('width') + ',' + this.get('height') + ')';
	}
});
exports.Rect = Rect;


/**
 * The string wrapper object.
 */
var nil;
var Nil = ValueObject.extend({
	/**
	 * Returns the string representation of the value and the argument that will
	 * be sent if the object is passed to POP.
	 * @return string
	 */
	toString: function () {
		return 'nil';
	}
});

/**
 * Returns the shared instance.
 * @return {Joj.Nil}
 */
Nil.sharedInstance = function () {
	if (!nil) {
		nil = new Nil();
	}
	return nil;
};

exports.Nil = Nil;
exports.nil = Nil.sharedInstance();

/**
 * Returns if the given value is an instance of Nil.
 * @param  {mixed}  testValue The value to test
 * @return {Boolean}
 */
Nil.isNil = function (testValue) {
	return testValue instanceof Nil;
};
exports.isNil = Nil.isNil;


/**
 * The proxy object builds the representation of an Objective-C object in the
 * QOQ environment.
 */
var ProxyObject = Backbone.Model.extend({
	/**
	 * The name of the represented class.
	 * @var string
	 */
	className: '',

	/**
	 * Options for the creation of the Objective-C object.
	 * @type {Array}
	 */
	options: [0],

	/**
	 * Indicates if the Objective-C object should automatically be created.
	 * @type {Boolean}
	 */
	createPopObject: true,
	
	/**
	 * The data received from POP.
	 * @var mixed
	 */
	_data: null,

	/**
	 * Returns a new proxy object for a object of the POP server.
	 * @return object
	 */
	initialize: function () {
		var dontSend = false,
			uuidSuffix, uuid;

		if (!this.get('id')) {
			// Construct the UUID with the timestamp
			uuidSuffix = ('' + new Date().getTime()).replace('.', '-');
			uuid = 'inst-' + this.get('className') + '-' + uuidSuffix;
			this.set('id', uuid);
		}
		
		if (!dontSend && this.get('createPopObject')) {
			this.createObjectInPop();
		}
		return this;
	},
	
	/**
	 * Returns a new proxy object for a object of the POP server.
	 * @param string|array $className The name of the represented class, or an array of arguments
	 * @return object
	 */
	off_initialize: function (className) {
		var dontSend = false,
			argumentsArray,
			uuidSuffix, uuid;

		if (arguments.length > 0) {
			className = '';
		}

		// Create an array of the function arguments
		argumentsArray = Array.prototype.slice.call(arguments);

		/*
		 * If the first argument is an array, it may be a dictionary with
		 * configurations for the Proxy Object.
		 */
		if (typeof className === 'array') {
			if (className['className']) {
				this.set('className', className['className']);
			}
			if (className['uuid']) {
				this.set('id', className['uuid']);
			} else if (className['id']) {
				this.set('id', className['id']);
			}
			if (className['dontSend']) {
				dontSend = className['dontSend'];
			} else if (className['>dontSend']) {
				dontSend = className['>dontSend'];
			}
			argumentsArray.shift();
		}
		console.log('# ' + className);

		if (!this.get('className')) {
			this.set('className', className);
		}
        
		if (!this.get('id')) {
			// Construct the UUID with the timestamp
			uuidSuffix = ('' + new Date().getTime()).replace('.', '-');
			uuid = 'inst-' + className + '-' + uuidSuffix;
			this.set('id', uuid);
		}
		
		
		if (!dontSend && argumentsArray.indexOf('>dontSend') === -1) {
			this.createObjectInPop(argumentsArray);
		}
		return this;
	},
	
	/**
	 * Creates the object in the POP server space.
	 *
	 * @param array<string>	$arguments The arguments
	 * @return void
	 */
	createObjectInPop: function () {
		var popData = null,
			options = this.options,
			identifier = this.get('id');

		if (Nil.isNil(options)) {
			options = [];
		}
		Joj.Runtime.sendCommand('new ' + this.get('className') + ' ' + identifier + ' ' + options.join(' '), false);
		console.log('# ' + 'new ' + this.get('className') + ' ' + identifier + ' ' + options.join(' '));
		/*
		 * Some Objective-C objects (i.e. NSURL) can not be retrieved until they
		 * are initialized.
		 * If you want the data to be fetched automatically, you can pass
		 * ">retrievePopData" as one of the arguments.
		 */
		if (options.indexOf('>retrievePopData') !== -1) {
			popData = Joj.Runtime.getValueForKeyPath(identifier, true);
			this.setData(popData);
		}
	},
	
	/**
	 * Returns the unique identifier of the object.
	 * @return string
	 */
	getUuid: function () {
		return this.get('id');
	},
	
	/**
	 * Sets the unique identifier of the object.
	 * @param string $uuid The new unique identifier
	 * @return void
	 */
	setUuid: function (uuid) {
		this.set('id', uuid);
	},
	
	/**
	 * Returns the value for the given key.
	 * @param	string	$name	The property key
	 * @return	mixed			The property's value
	 */
	get: function (name) {
		var result;
		result = Backbone.Model.prototype.get.call(this, name);
		if (result === undefined) {
			if (ProxyObject.prototype[name]) {
				result = ProxyObject.prototype[name];
			} else if (name === 'id') {
				result = null;
			} else {
				result = Joj.Runtime.getValueForKeyPath(this.getUuid() + '.' + name);
			}
		}
		return result;
	},
	
	/**
	 * Returns the data received from POP.
	 *
	 * @param mixed
	 * @return void
	 */
	getData: function () {
		var popData;
		if (!this.get('_data')) {
			popData = Joj.Runtime.getValueForKeyPath(identifier, true);
			this.setData(popData);
		}
		return this.get('_data');
	},
	
	/**
	 * Set the data received from POP.
	 *
	 * @param mixed
	 * @return void
	 */
	setData: function (value) {
		this.set('_data', value);
	},

	/**
	 * Returns the string representation of the value and the argument that will
	 * be sent if the object is passed to POP.
	 * @return string
	 */
	toString: function () {
		return '' + this.getUuid();
	},

	/**
	 * Tries to dynamically resolve methods.
	 *
	 * If the method name starts with 'set' setValueForKey() will be called.
	 * If the method name starts with 'get' getValueForKey() will be called.
	 * All other method names will be parsed with convertMethodNameToCommand().
	 * @param string $name The name of the method
	 * @param array $arguments Arguments sent to the method
	 * @return mixed
	 */
	call: function (methodName) {
		var command,
			response,
			argumentsArray = Array.prototype.slice.call(arguments);
		
		argumentsArray.shift();
		command = Joj.Runtime.convertMethodNameToCommand(this, methodName, argumentsArray);
		response = Joj.Runtime.sendCommand(command);
		if (response && response !== Nil.sharedInstance()) {
			return response;
		}
		return Nil.sharedInstance();
	}
});
exports.ProxyObject = ProxyObject;