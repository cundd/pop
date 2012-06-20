<?php
namespace Qoq;

/*
 * @license
 */

require_once(__DIR__ . '/Functions.php'); 

use \Qoq\Nil as Nil;
use \Qoq\ProxyObject as ProxyObject;

/**
 * The runtime of the QOQ system.
 */
class QoqRuntime {
	/**
	 * The path of the named pipe.
	 * 
	 * @var string
	 */
	protected $pipeName = '';
	
	/**
	 * The file pointer of the pipe.
	 * 
	 * @var integer
	 */
	protected $pipe = NULL;
	
	/**
	 * The main application.
	 * 
	 * @var object
	 */
	protected $application = NULL;
    
    /**
     * Indicates if the runtime should test if the POP server is still available and shut down if it isn't.
     * 
     * @var boolean
     */
    protected $terminateIfServerIsNotAlive = FALSE;
	
	/**
	 * Indicates if the runtime is used standalone.
	 *
	 * The standalone mode is mainly for debugging.
	 * 
	 * @var boolean
	 */
	protected $runStandalone = -1;
	
	/**
	 * The shared runtime instance.
	 * 
	 * @var QoqRuntime
	 */
	static protected $sharedInstance = NULL;
	
	/**
	 * Initializes the runtime system.
	 * 
	 * @return QoqRuntime
	 */
	public function __construct(){
		if(self::$sharedInstance === NULL){
			// Initialize the runtime
			spl_autoload_register(array(__CLASS__, 'loadClassFile'));
			register_shutdown_function(array(__CLASS__, 'shutDown'));
			
			// Set the error output options
			if($this->getRunStandalone()){
				error_reporting(E_ALL);
				ini_set('display_errors', TRUE);
			} else {
				ini_set('display_errors', FALSE);
			}
			
			// Load nil
			self::loadClassFile('Qoq\Nil');
			self::$sharedInstance = $this;
		}
	}
	
	
	
	/* MWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWM */
	/* MAIN RUN LOOP      WMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWM */
	/* MWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWM */
	/**
	 * Starts the run loop.
	 * 
	 * @return void
	 */
	public function run(){
		$runCounter = 0;
        $checkIfServerIsAlive = 0;
        $this->getApplication();
		
        $this->pipe = fopen($this->getPipeName(), 'r');
		while(1){
			$line = trim(fread($this->pipe, 1024));
			if($line){
                $this->dispatch($line);
			}
            
            // Check if the server is alive every 1000th run
            if($this->terminateIfServerIsNotAlive && !$checkIfServerIsAlive--){
                $this->checkIfServerIsAlive();
                $checkIfServerIsAlive = 100;
            }
			
			if(++$runCounter > 1000 && $this->runStandalone){
				exit();
			}
			usleep(100000);
		}
	}
	
	
    
	/* MWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWM */
	/* DISPATCHING       MWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWM */
	/* MWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWM */
	/**
	 * Dispatch the command received from the POP server.
	 * 
	 * @param string $inputCommand
	 * @return void
	 */
	public function dispatch($inputCommand){
		$app = $this->getApplication();
		$commandParts = explode(' ', $inputCommand);
		$app->setCommandParts($commandParts);
        
        try{
            call_user_func_array(array($app, 'handle'), $commandParts);
        } catch(Exception $e){
            self::sendCommand('throw ' . get_class($e) . ' ' . $e->getMessage() . ' ' . $e->getCode());
        }
	}
	
	/**
	 * Returns the application object.
	 * 
	 * @return object
	 */
	public function getApplication(){
		if(!$this->application){
			$settings = require_once(__DIR__ . '/../../../../Configuration/Settings.php');
			$applicationControllerClass = $settings['PrincipalClass'];
			$this->application = self::makeInstance($applicationControllerClass);
			
			$this->terminateIfServerIsNotAlive = $settings['TerminateIfServerIsNotAlive'];
		}
		return $this->application;
	}
	
	
	
	/* MWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWM */
	/* CLASS LOADING AND OBJECT CREATION    WMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWM */
	/* MWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWM */
	/**
	 * Loads the file of the given class.
	 * 
	 * @param string $class The class name including the namespace
	 * @return boolean  Returns TRUE if the class file could be loaded, otherwise FALSE
	 */
	static public function loadClassFile($class){
		$controllerClassPath = str_replace('\\', '/', $class);
		$firstSlashPosition = strpos($controllerClassPath, '/');
		if($firstSlashPosition === FALSE){
			return FALSE;
		}
		$package = substr($controllerClassPath, 0, $firstSlashPosition);
		$relativeClassPathFromPackage = substr($controllerClassPath, $firstSlashPosition);
		$absoluteClassPathApplicationDirectory = __DIR__ . '/../../../Application/' . $package . '/Classes/' . $relativeClassPathFromPackage . '.php';
		$absoluteClassPathFrameworkDirectory = __DIR__ . '/../../../Framework/' . $package . '/Classes/' . $relativeClassPathFromPackage . '.php';
		
		if(file_exists($absoluteClassPathApplicationDirectory)){
			require_once($absoluteClassPathApplicationDirectory);
		} else {
			require_once($absoluteClassPathFrameworkDirectory);
		}
		return class_exists($class, FALSE);
	}
	
	/**
	 * Creates and returns an instance of the given class.
	 *
	 * As a convencion the constructors of classes instantiated with
	 * makeInstance() have to take an array as argument.
	 *
	 * 
	 * 
	 * @param string $class The class name including the namespace
	 * @param array<mixed> $arguments An array of arguments to pass to the constructor
	 * @return object  The instance of the class, or NULL on error
	 */
	static public function makeInstance($class, $arguments = array()){
		 // Try to load the class file
		if(self::loadClassFile($class)){
			if(func_num_args() > 0){
				return new $class($arguments);
			} else {
				return new $class();
			}
		} else
		// If the class name doesn't contain a backslash try to create a Cocoa instance
		if(strpos($class, '\\') === FALSE){
			$proxyObject = new ProxyObject($class);
			$identifier = $proxyObject->getUuid();
			
			if(!is_array($arguments)){
				$arguments = array($arguments);
			}
			self::sendCommand('new ' . $class . ' ' . $identifier . ' ' . implode(' ', $arguments));
			$popData = self::getValueForKey($identifier);
			$proxyObject->setData($popData);
			return $proxyObject;
		}
		return NULL;
	}
	
	
	
	/* MWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWM */
	/* COMMUNICATION WITH POP      MWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWM */
	/* MWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWM */
	/**
	 * Queries the POP server for the value for the given identifier.
	 * 
	 * @param string $identifier The identifier of the value to get
	 * @return object  The value for the identifier
	 */
	static public function getValueForKeyPath($identifier){
		$command = "get $identifier";
		self::sendCommand($command);
		return self::sharedInstance()->waitForResponse();
	}
	/**
	 * @see getValueForKeyPath()
	 */
	static public function getValueForKey($identifier){
		return self::getValueForKeyPath($identifier);
	}
	
	/**
	 * Sets the new value for the identifier of the POP server.
	 * 
	 * @param string $identifier The identifier of the value to set
	 * @param object $value The new value to set
	 * @return void
	 */
	static public function setValueForKeyPath($identifier, $value){
		$value = self::convertValueToArgumentString($value);
		$command = "set $identifier $value";
		self::sendCommand($command);
	}
	/**
	 * @see setValueForKeyPath()
	 */
	static public function setValueForKey($identifier, $value){
		self::setValueForKeyPath($identifier, $value);
	}
	
	/**
	 * Sends the given command to the POP server.
	 * 
	 * @param string $command The command to send
	 * @return void
	 */
	static public function sendCommand($command){
		echo $command . PHP_EOL;
	}
	
	/**
	 * Waits and returns the first response line from the POP server.
	 * 
	 * @return object  Returns the string representation
	 */
	public function waitForResponse(){
		while(1){
			$line = trim(fread($this->pipe, 1024));
			if($line){
				return $line;
			}
			usleep(100000);
		}
	}
	
	/**
	 * Returns the path of the named pipe.
	 * 
	 * @return string
	 */
	public function getPipeName(){
		if(!$this->pipeName){
			$this->pipeName = '/tmp/qoq_pipe';
		}
		return $this->pipeName;
	}
	
	/**
	 * Sets the path of the named pipe.
	 * 
	 * @param string $newName The new name of the pipe
	 * 
	 * @return void
	 */
	public function setPipeName($newName){
		$this->pipeName = $newName;
	}
	
	
	
	
	/* MWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWM */
	/* STATIC HELPERS    MWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWM */
	/* MWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWM */
	/**
	 * Returns the shared instance.
	 * 
	 * @return QoqRuntime
	 */
	static public function sharedInstance(){
		if(self::$sharedInstance === NULL){
			new QoqRuntime();
		}
		return self::$sharedInstance;
	}
	
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
	static public function convertCommandToMethodName($command){
		$command = trim($command);
		$commandParts = explode(' ', $command);
		$command = $commandParts[2];
		
		// Remove the colons from the command
		if(strpos($command, ':')){
			// Split the command string into words
			$words = explode(':', strtolower($command));
			
			$command = '';
			foreach ($words as $word) {
				$command .= ucfirst(trim($word));
			}
		}
		return $command;
	}
	
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
	static public function convertMethodNameToCommand($identifier, $methodName, $arguments = array()){
		$convertedArguments = array();
		$argument = reset($arguments);
		while ($argument){
			$convertedArguments[] = self::convertValueToArgumentString($argument);
			$argument = next($arguments);
		}
		
		if(is_object($identifier)){
			$identifier = $identifier->getUuid();
		}
		
		if(strpos($methodName, '_') || count($arguments) > 0){
			$commandName = str_replace('_', ':', $methodName) . ':';
		}
		return 'exec ' . $identifier . ' ' . $commandName . ' ' . implode(' ', $convertedArguments);
	}
	
	/**
	 * Returns the command string for the given value.
	 * 
	 * @param mixed $value The value
	 * @return string  The string representation of the value
	 */
	static public function convertValueToArgumentString($value){
		$result = '';
		if($value === Nil::nil()){
			$result = 'nil';
		} else if(is_string($value) && substr($value, 0 ,1) !== '@'){
			$result = self::prepareString($value);
		} else if(is_int($value)){
			$result = "(int)$value";
		} else if(is_float($value)){
			$result = "(float)$value";
		} else if(is_object($value)){
			$result = $value . '';
		} else {
			$result = '' . $value;
		}
		return $result;
	}
	
	/**
	 * Preparse the string for sending.
	 * 
	 * @param string $string
	 * @return string  Returns the prepared string
	 */
	static public function prepareString($string){
        $string = self::escapeString($string);
        return '@"' . $string . '"';
	}
    
    /**
	 * Escape the string.
	 * 
	 * @param string $string
	 * @return string  Returns the escaped string
	 */
	static public function escapeString($string){
        return str_replace(' ', '&_', $string);
	}
	
	/**
	 * Dumps a given variable (or the given variables) as a command
	 * 
	 * @param mixed $var1
	 *
	 * @return string The printed content.
	 */
	static public function pd($var1 = '__iresults_pd_noValue') {
		$args = func_get_args();
		$output = '';
		
		ob_start();
		foreach ($args as $var) {
			var_dump($var);
		}
		$output = '# ' . ob_get_clean();
		$output = str_replace(array('\n', '\r'), '\n# ', $output);
		
		self::sendCommand($output);
		return $output;
	}
	/**
	 * @see pd()
	 */
	static public function predump(){
		$args = func_get_args();
		return call_user_func_array(array(self, 'pd'), $args);
	}
	
	/**
	 * Handles shutdown functions.
	 * 
	 * @return void
	 */
	static public function shutDown(){
		echo '# Shutdown';
	}
	
	
	
	/* MWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWM */
	/* HELPERS           MWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWM */
	/* MWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWM */
	/**
	 * Checks if the POP server is alive and quit the process if it isn't.
	 * 
	 * @return void
	 */
	public function checkIfServerIsAlive(){
		$popServerPid = $this->getPopServerPid();
        if($popServerPid === FALSE){
            self::sendCommand('# QOQ: The POP server PID couldn\'t be fetched.');
			$this->terminateIfServerIsNotAlive = FALSE;
            return;
        }        
        $shellCommand = "ps -A $popServerPid|grep $popServerPid";
		$processInfo = exec($shellCommand);
        
		// If the command returned something like 
		if(!trim($processInfo)){
			$shutDownMessage = '# QOQ: The POP server doesn\'t seem to be alive. QOQ will now exit.';
			trigger_error($shutDownMessage, E_USER_NOTICE);
			self::sendCommand($shutDownMessage);
            usleep(1000);
			exit();
		}
	}
	
	/**
	 * Returns if the runtime is used standalone.
	 * 
	 * @return boolean
	 */
	public function getRunStandalone(){
		if($this->runStandalone === -1){
			if($this->getPopServerPid() === FALSE){
				$this->runStandalone = TRUE;
			} else {
				$this->runStandalone = FALSE;
			}
		}
		return $this->runStandalone;
	}
	
	/**
	 * Returns the process ID of the POP server.
	 * 
	 * @return integer
	 */
	public function getPopServerPid(){
		return getenv('popServerPid');
	}
}
    
?>