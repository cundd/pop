/*
 * @license
 */

var Backbone     = require('Backbone'),
	plist        = require('plist'),
	fs           = require('fs'),
	os           = require('os'),
	sleep        = require('sleep'),
	Joj          = require('./ValueObject');


var calledApplicationDidFinishLaunching = false,
	applicationSettings                 = null,
	useResourceDirectoryAsBase          = -1,
	/**
	 * The shared runtime instance
	 * @var Runtime
	 */
	sharedInstance = null;

/**
 * The runtime of the JOJ system.
 */
var Runtime = Backbone.Model.extend({
	/**
	 * The path of the named pipe
	 * @var string
	 */
	pipeName: '',
	
	/**
	 * The file pointer of the pipe
	 * @var integer
	 */
	pipe: null,
	
	/**
	 * The main application
	 * @var object
	 */
	application: null,
	
	/**
	 * The time to wait for a response in microseconds
	 * @var integer
	 */
	waitTime: 2000000,
	
    /**
     * Indicates if the runtime should test if the POP server is still available and shut down if it isn't.
     * @var boolean
     */
    terminateIfServerIsNotAlive: false,
	
	/**
	 * Indicates if the runtime is used standalone.
	 *
	 * The standalone mode is mainly for debugging
	 * @var boolean
	 */
	runStandalone: -1,
	
	/**
	 * The sleep interval in microseconds
	 * @var int
	 */
	sleepTime: 5000,

	
	
	
	/**
	 * Initializes the runtime system
	 * @return QoqRuntime
	 */
	initialize: function () {
		if (sharedInstance === null) {
			sharedInstance = this;
		}
	},
	
	
	
	/* MWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWM */
	/* MAIN RUN LOOP      WMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWM */
	/* MWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWM */
	/**
	 * Starts the run loop
	 * @return void
	 */
	run: function () {
		var _this = this,
			runCounter = 0,
			checkIfServerIsAlive = 0;
		
		this.getApplication();

		process.stdin.resume();
		process.stdin.on('data', function (chunk) {
			// As long there is input from POP handle it
			_this.dispatch(chunk + '');

			// Check if the server is alive every 1000th run
            if (this.terminateIfServerIsNotAlive && !(checkIfServerIsAlive -= 1)) {
                this.checkIfServerIsAlive();
                checkIfServerIsAlive = 100;
            }
            
            if ((runCounter += 1) > 1000 && this.runStandalone) {
                process.exit();
            }
		});
	},
	
	
    
	/* MWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWM */
	/* DISPATCHING       MWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWM */
	/* MWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWM */
	/**
	 * Dispatch the command received from the POP server
	 * @param string $inputCommand
	 * @return void
	 */
	dispatch: function (inputCommand) {
		var app, commandParts, handleFunction;
		app = this.getApplication();
		commandParts = inputCommand.split(' ');
		app.setCommandParts(commandParts);
        
        try{
			handleFunction = app.handle;
			handleFunction.apply(app, commandParts);
        } catch(e) {
            Runtime.sendCommand('throw ' + e);
        }
	},
	
	/**
	 * Returns the application object
	 * @return object
	 */
	getApplication: function () {
		var settings = [],
			fileContents;
		if (!this.application) {
			applicationControllerClass = this.getControllerClassNameFromInfoPlist();
			if (!applicationControllerClass) {
				fileContents = fs.readFileSync(__dirname + '/../../../../Configuration/Settings.json', 'utf8');
				settings = JSON.parse(fileContents);
				//settings = require('./../../../../Configuration/Settings.json');
				applicationControllerClass = settings['PrincipalClass'];
			}
			this.application = this.makeInstance(applicationControllerClass);
            
			/*
			 * Call the applications applicationDidFinishLaunching() if the
			 * method exists
			 */
			if (this.application && this.application['applicationDidFinishLaunching'] && (typeof this.application['applicationDidFinishLaunching'] === 'function')) {
				this.application.applicationDidFinishLaunching();
			}
			
			this.terminateIfServerIsNotAlive = false;
			if (settings['TerminateIfServerIsNotAlive']) {
				this.terminateIfServerIsNotAlive = settings['TerminateIfServerIsNotAlive'];
			}
		}
		return this.application;
	},
	
	/**
	 * Returns the QOQ controller class name from the info plist
	 * @return	string		Returns the class name or false if it couldn't be read
	 */
	getControllerClassNameFromInfoPlist: function () {
		applicationSettings = this.getSettingsFromInfoPlist();
		if (applicationSettings && applicationSettings['QoqPrincipalClass']) {
			return applicationSettings['QoqPrincipalClass'];
		}
		return false;
	},
	
	/**
	 * Returns the settings read from the info plist
	 * @return	array<string>
	 */
	getSettingsFromInfoPlist: function () {
		if (!applicationSettings) {
			var plistPath = './../../../../../../Info.plist';

			if (fs.existsSync(plistPath)) {
				plist.parseFile(plistPath, function(err, obj) {
					if (err) throw err;
					applicationSettings = obj;
				});
			}
			
		}
		return applicationSettings;
	},
	
	/* MWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWM */
	/* CLASS LOADING AND OBJECT CREATION    WMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWM */
	/* MWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWM */
	/**
	 * Loads the file of the given class
	 * @param string $class The class name including the namespace
	 * @return boolean  Returns true if the class file could be loaded, otherwise false
	 */
	loadClassFile: function (className) {
		var loadedExport,
			packagePath = __dirname + '/../../..',
			controllerClassPath = className.replace(/\./g, '/'),
			firstDotPosition = controllerClassPath.indexOf('/');
        
        // If the class name doesn't contain a backslash try to create a Cocoa instance
		packageName = controllerClassPath.substring(0, firstDotPosition);
		relativeClassPathFromPackage = controllerClassPath.substring(firstDotPosition);
		
		// Check if the classes should be searched inside the resource directory
		if (useResourceDirectoryAsBase === -1) {
			useResourceDirectoryAsBase = fs.existsSync(packagePath);
		}
		
		absoluteClassPathResourceDirectory    = packagePath + '/Application/' + packageName + '/Classes/' + relativeClassPathFromPackage + '.js';
		absoluteClassPathFrameworkDirectory   = packagePath + '/Framework/' + packageName + '/Classes/' + relativeClassPathFromPackage + '.js';
		
		//console.log('PKG', packageName, controllerClassPath, absoluteClassPathResourceDirectory);
		/*
		console.log('L:', __dirname + '/' + relativeClassPathFromPackage + '.js', 
			fs.existsSync(__dirname + '/' + relativeClassPathFromPackage + '.js')
			);
		// */

		if (fs.existsSync(__dirname + '/' + relativeClassPathFromPackage + '.js')) {
			loadedExport = require(__dirname + '/' + relativeClassPathFromPackage + '.js');
		} else if (useResourceDirectoryAsBase && fs.existsSync(absoluteClassPathResourceDirectory)) {
			loadedExport = require(absoluteClassPathResourceDirectory);
		} else if (fs.existsSync(absoluteClassPathFrameworkDirectory)) {
			loadedExport = require(absoluteClassPathFrameworkDirectory);
		} else {
			console.log('# Could not load class "' + className + '"');
			return false;
		}
        return loadedExport;
	},
	
	/**
	 * Creates and returns an instance of the given class.
	 *
	 * As a convencion the constructors of classes instantiated with
	 * makeInstance() have to take an array as argument.
	 * @param string        className   The class name including the namespace
	 * @param array<mixed>  arguments   An array of arguments to pass to the constructor
	 * @return object                   The instance of the class, or null on error
	 */
	makeInstance: function (className, argumentArray) {
		var namespace;
		// Try to load the class file
		if ((namespace = this.loadClassFile(className))) {
			if (arguments.length > 1) {
				return new namespace[className](argumentArray);
			} else {
				return new namespace[className]();
			}
		} else
		// If the class name doesn't contain a backslash try to create a Cocoa instance
		if (className.indexOf('\\') === -1) {
			proxyObject = new Joj.ProxyObject(className);
			return proxyObject;
		}
		return null;
	},
	
	/**
	 * Creates a Proxy Object from the result of a POP get request
	 * @param string $value The value returned from a POP get request (i.e. <NSView: 0x4004c8b40>)
	 * @param string $identifier The identifier which retrieved the result
	 * @return ProxyObject  Returns the Proxy Object representing the POP result
	 */
	makeInstanceFromPopReturn: function (value, identifier) {
		var proxyObject,
			colonLocation = value.indexOf(':'),
			classNameStart = (value.charAt(0) === '<') ? 1 : 0;
		className = value.substring(classNameStart, colonLocation - 1);
		
		proxyObject = new Joj.ProxyObject(className, '>dontSend');
		proxyObject.set('id', identifier);
		proxyObject.set('data', value);
		return proxyObject;
	},
	
	
	
	/* MWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWM */
	/* COMMUNICATION WITH POP      MWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWM */
	/* MWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWM */
	/**
	 * Waits and returns the first response line from the POP server.
	 *
	 * @param	integer		$microseconds	The microseconds to wait for the response
	 * @return	mixed						Returns the parsed response
	 */
	waitForResponse: function (microseconds) {
		var sleepTime, buffer, bytesRead;
		sleepTime = this.sleepTime / 1000;
		if (arguments.length === 0) {
			microseconds = this.getWaitTime() / 1000;
		}
		if (microseconds > 0) {
			process.stdin.resume();
			
			bytesRead = fs.fstatSync(process.stdin.fd).size;
			buffer = bytesRead > 0 ? fs.readSync(process.stdin.fd, bytesRead)[0] : '';

			if (bytesRead) {
				if (buffer + '' === '(null)') {
					return Joj.Nil.sharedInstance();
				} else if (buffer + '' === '(void)') {
					return Joj.Nil.sharedInstance();
				} else if (buffer + '' === '(bool)true') {
					return false;
				} else if (buffer + '' === '(bool)true') {
					return true;
				}
				return buffer;
			}
			microseconds -= sleepTime;
			sleep.usleep(sleepTime);
			this.waitForResponse(microseconds);
		}
		return Joj.Nil.sharedInstance();
	},
	
	/**
	 * Sets the file position indicator to the end of the pipe
	 * @return	void
	 */
	clearResponseBuffer: function () {
		// fs.truncateSync(process.stdin.fd, 0);
	},
	
	/**
	 * Returns the time to wait for a response in microseconds.
	 *
	 * @return integer
	 */
	getWaitTime: function () {
		return this.waitTime;
	},
	
	/**
	 * Sets the time to wait for a response in microseconds.
	 *
	 * @param	integer $value	The new microseconds to wait
	 * @return	integer			The previous value
	 */
	setWaitTime: function (value) {
		var oldWaitTime = this.waitTime;
		this.waitTime = value;
		return oldWaitTime;
	},
	
	/**
	 * Returns the path of the named pipe
	 * @return string
	 */
	getPipeName: function () {
		if (!this.pipeName) {
			this.pipeName = '/tmp/qoq_pipe';
		}
		return this.pipeName;
	},
	
	/**
	 * Sets the path of the named pipe
	 * @param string $newName The new name of the pipe
	 * @return void
	 */
	setPipeName: function (newName) {
		this.pipeName = newName;
	},
	
	
	
	

  //   error: function ($errno, $errstr = '', $errfile = '', $errline = '') {
		// $output = '# ';
  //       if ($errstr) {
  //           $output = "#$errno: $errstr $errfile @ $errline";
  //       } else {
		//	$output = "#$errno";
		// }
		
		// ob_start();
		// debug_print_backtrace();
		// $output .= '# ' . ob_get_clean();
		
		// $output = implode("\n# \t", str_split($output, 80));
		// $output = str_replace(array('\n', '\r'), '\n# ', $output);
		
		// this.sendCommand($output);
		// return $output;
  //   }
	
	
	
	/* MWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWM */
	/* HELPERS           MWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWM */
	/* MWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWM */
	/**
	 * Checks if the POP server is alive and quit the process if it isn't
	 * @return void
	 */
	checkIfServerIsAlive: function () {
		return;

		var popServerPid = this.getPopServerPid(),
			shellCommand,
			processInfo;

        if (popServerPid === false) {
            this.sendCommand('# QOQ: The POP server PID couldn\'t be fetched.');
			this.terminateIfServerIsNotAlive = false;
            return;
        }
        shellCommand = "ps -A " + popServerPid + "|grep " + popServerPid;
		processInfo = exec(shellCommand);
        
		// If the command returned something like
		if (!processInfo) {
			shutDownMessage = '# QOQ: The POP server doesn\'t seem to be alive. QOQ will now exit.';
			this.sendCommand(shutDownMessage);
            process.exit();
		}
	},
	
	/**
	 * Returns if the runtime is used standalone
	 * @return boolean
	 */
	getRunStandalone: function () {
		if (this.runStandalone === -1) {
			if (this.getPopServerPid() === false) {
				this.runStandalone = true;
			} else {
				this.runStandalone = false;
			}
		}
		return this.runStandalone;
	},
	
	/**
	 * Returns the process ID of the POP server
	 * @return integer
	 */
	getPopServerPid: function () {
		if (process.env['popServerPid']) {
			return process.env['popServerPid'];
		}
		return false;
	}
});


/* MWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWM */
/* STATIC COMMUNICATION WITH POP      WMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWM */
/* MWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWM */
/**
 * Queries the POP server for the value for the given identifier
 * @param string $identifier The identifier of the value to get
 * @param boolean $dontCreateProxy Define if a proxy object should be created, if the result indicates an Objective-C object
 * @return object  The value for the identifier
 */
Runtime.getValueForKeyPath = function (identifier, dontCreateProxy) {
	var command;
	if (arguments.length < 2) {
		dontCreateProxy = false;
	}
	
	command = "get " + identifier;
	value = this.sendCommand(command);
	if (dontCreateProxy === false && !Joj.isNil(value)) {
		if (value.substring(0, 1) === '<' && value.indexOf(': 0x') !== -1) {
			value = this.makeInstanceFromPopReturn(value, identifier);
		} else if (value.substring(0, 2) === 'NS' && /^NS[a-zA-Z]+ColorSpace/.test(value)) {
			value = this.makeInstanceFromPopReturn('NSColor :' + value, identifier);
		}
    }
    return value;
};
/**
 * @see getValueForKeyPath()
 */
Runtime.getValueForKey = function (identifier) {
	return this.getValueForKeyPath(identifier);
};

/**
 * Sets the new value for the identifier of the POP server
 * @param string $identifier The identifier of the value to set
 * @param object $value The new value to set
 * @return void
 */
Runtime.setValueForKeyPath = function (identifier, value) {
	value = this.convertValueToArgumentString(value);
	command = "set " + identifier + " " + value;
	this.sendCommand(command);
};
/**
 * @see setValueForKeyPath()
 */
Runtime.setValueForKey = function (identifier, value) {
	this.setValueForKeyPath(identifier, value);
};

/**
 * Sends the given command to the POP server and returns the response
 * @param   string    $command          The command to send
 * @param   boolean   $waitForResponse  Set this to false if you don't want to wait for the response, in this case nil will be returned
 * @return  mixed                       Returns the response from POP
 */
Runtime.sendCommand = function (command, waitForResponse) {
	if (arguments.length < 2) {
		waitForResponse = true;
	}
	// Clear the response buffer
	Runtime.sharedInstance().clearResponseBuffer();
	
	// Trim and send the command
	trimmedCommand = command.trim();
	console.log(trimmedCommand);
    
    if (!waitForResponse || trimmedCommand.substring(0) === '#' || trimmedCommand.substring(0) === '>' || trimmedCommand.substring(0, 2) === '//') {
		return Joj.Nil.sharedInstance();
	}
	return Runtime.sharedInstance().waitForResponse();
};


/* MWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWM */
/* STATIC HELPERS    MWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWM */
/* MWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWM */
/**
 * Returns the shared instance
 * @return QoqRuntime
 */
Runtime.sharedInstance = function () {
	if (sharedInstance === null) {
		new Runtime();
	}
	return sharedInstance;
};

/**
 * Converts the command string to a method name.
 *
 * @example:
 * Converts
 *  loadNibNamed:owner:
 *
 * into
 *  loadNibNamedOwner
 *
 * @param string $command The command to convert
 * @return string  Returns the converted method name
 */
Runtime.convertCommandToMethodName = function (command) {
	var commandParts = command.split(' '),
		words, word, i = 0;
	command = commandParts[2];
	
	// Remove the colons from the command
	if (command.indexOf(':')) {
		// Split the command string into words
		words = command.split(':');
		
		command = '';
		while ((word = words[i])) {
			command += word.charAt(0).toUpperCase() + word.slice(1);
			i += 1;
		}
	}
	return command;
};

/**
 * Converts the method name, object and arguments to a command.
 *
 * @example:
 * Converts
 *  loadNibNamed_Owner
 *
 * into
 *  loadNibNamed:owner:
 *
 * @param object|string $identifier Either the object that calls the method or an identfier string
 * @param string $methodName The method name to convert
 * @param array<mixed> $arguments The arguments to pass
 * @return string  Returns the command
 */
Runtime.convertMethodNameToCommand = function (identifier, methodName, commandArguments) {
	var i = 0,
		length = 0,
		argument,
		preparedArgumentCollection = [];

	if (arguments.length < 3) {
		commandArguments = [];
	}

	length = commandArguments.length;
	for (; i < length; i++) {
		argument = commandArguments[i];
		argument = this.convertValueToArgumentString(argument);
		preparedArgumentCollection.push(argument);
	}
	
	if (typeof identifier === 'object') {
		identifier = identifier.get('id');
	}
	
	if (methodName.indexOf('_') !== -1 || length > 0) {
		methodName = methodName.replace('_', ':');
		if (methodName.charAt(methodName.length - 1) !== ':') {
			methodName += ':';
		}
	}
	Runtime.pd(preparedArgumentCollection);
	return 'exec ' + identifier + ' ' + methodName + ' ' + preparedArgumentCollection.join(' ');
};

/**
 * Returns the command string for the given value.
 *
 * If the given value is a string, but begins with an at-sign ('@') the string
 * will not be prepared
 * @param mixed $value The value
 * @return string  The string representation of the value
 */
Runtime.convertValueToArgumentString = function (value) {
	var preparedArgument;
	switch (typeof value) {
		case 'boolean':
			preparedArgument = '(bool)' + value;
			break;

		case 'number':
			preparedArgument = '(float)' + value;
			break;

		case 'string':
			if (value === 'nil') {
				preparedArgument = 'nil';
			} else if (value.charAt(0) === '@') {
				preparedArgument = value.substr(1);
			} else {
				preparedArgument = Runtime.prepareString(value);
			}
			break;

		// case 'null':
		default:
			preparedArgument = 'nil';
			break;
	}
	return preparedArgument;
};

/**
 * Preparse the string for sending
 * @param string $string
 * @return string  Returns the prepared string
 */
Runtime.prepareString = function (inputString) {
	inputString = this.escapeString(inputString);
    return '@"' + inputString + '"';
};

/**
 * Escape the string
 * @param string $string
 * @return string  Returns the escaped string
 */
Runtime.escapeString = function (inputString) {
    return inputString.replace(' ', '&_');
};

/**
 * Dumps a given variable (or the given variables) as a command
 * @param mixed $var1
 * @return string The printed content.
 */
Runtime.pd = function (var1) {
	var i = 0,
		argument,
		output = '',
		EOL = os.EOL,
		length = arguments.length,
		runStandalone = Runtime.sharedInstance().getRunStandalone();
	
	
	for (; i < length; i++) {
		argument = arguments[i];
		if (runStandalone) {
			console.log(argument);
		} else {
			output += Runtime.debug(argument);
		}
	}

	if (!runStandalone) {
		output = '# ' + output.replace(new RegExp(EOL, 'g'), EOL + '# ');
		console.log(output);
	}
};
/**
 * @see pd()
 */
Runtime.predump = function () {
	return this.pd.apply(this, arguments);
};

/**
 * Returns debug information about the given variable
 * @param  {mixed} variable     The variable to debug
 * @param  {Integer} depth      The current depth
 * @return {String}             The debug output
 */
Runtime.debug = function (variable, depth) {
	var key,
		property,
		output = '',
		maxDepth = 5,
		EOL = os.EOL,
		padding,
		keyPadding;

	
	if (arguments.length < 2) {
		depth = 1;
	}

	padding = new Array(depth + 1).join('   ');
	keyPadding = new Array(depth).join('   ');
	
	if (depth > maxDepth) {
		return padding + '(ML)' + EOL;
	}
	
	switch (typeof variable) {
		case 'object':
			for (key in variable) {
				property = variable[key];
				if (property !== variable) {
					output += keyPadding + key + ': ' + EOL + Runtime.debug(property, depth + 1);
				}
			}
			break;

		case 'function':
			output = padding + '(function)';
			break;

		default:
			output = padding + '(' + typeof variable + ')' + variable;
	}
	return output + EOL;
};

/**
 * Returns the path of the installation
 * @return string
 */
Runtime.getBasePath = function () {
	return './../../../../';
};

/**
 * Sets a breakpoint.
 * @param string	$identifier		An optional identifier of an object to debug
 * @return	void
 */
Runtime.breakpoint = function (identifier) {
	if (arguments.length < 1) {
		identifier = '';
	}
	this.pd('Breakpoint = ');
	this.sendCommand('breakpoint ' + identifier);
};
exports.Runtime = Runtime;