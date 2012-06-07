<?php
namespace Qoq;

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
			spl_autoload_register(array(__CLASS__, 'loadClassFile'));
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
		$this->pipe = fopen($this->getPipeName(), 'r');
		while(1){
			$line = trim(fread($this->pipe, 1024));
			if($line){
                $this->dispatch($line);
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
		$app->handle($inputCommand);
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
		
		if(file_exists($absoluteClassPathFrameworkDirectory)){
			require_once($absoluteClassPathFrameworkDirectory);
		} else {
			require_once($absoluteClassPathApplicationDirectory);
		}
		return class_exists($class, FALSE);
	}
	
	/**
	 * Creates and returns an instance of the given class.
	 * 
	 * @param string $class The class name including the namespace
	 * @return object  The instance of the class, or NULL on error
	 */
	static public function makeInstance($class){
		if(self::loadClassFile($class)){
			return new $class;
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
		$value = self::getCommandStringForValue($value);
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
	/* HELPERS           MWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWM */
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
	 * Returs the command string for the given value.
	 * 
	 * @param mixed $value Description
	 * @return string  The string representation of the value
	 */
	static public function getCommandStringForValue($value){
		$result = '';
		if(is_string($value)){
			$result = self::prepareString($value);
		} else if(is_int($value)){
			$result = "(int)$value";
		} else if(is_float($value)){
			$result = "(float)$value";
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
        $string = str_replace(' ', '&_', $string);
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
}
    
?>