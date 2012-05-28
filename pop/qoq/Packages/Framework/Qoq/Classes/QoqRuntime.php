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
	 * Initializes the runtime system.
	 * 
	 * @return QoqRuntime
	 */
	public function __construct(){
		
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
		
        $retrievedValue = $this->getValueForKeyPath('textfield.stringValue');
        $command = 'exec window setTitle: ' . $this->getCommandStringForValue($retrievedValue);
        $this->sendCommand($command);
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
			// 'SampleApplication/Controller/StandardController',
			
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
	public function loadClassFile($class){
		$controllerClassPath = str_replace('\\', '/', $class);
		$firstSlashPosition = strpos($controllerClassPath, '/');
		if($firstSlashPosition === FALSE){
			return FALSE;
		}
		$package = substr($controllerClassPath, 0, $firstSlashPosition);
		$relativeClassPathFromPackage = substr($controllerClassPath, $firstSlashPosition);
		$absoluteClassPath = __DIR__ . '/../../../' . $package . '/Classes/' . $relativeClassPathFromPackage;
		require_once($absoluteClassPath);
		
		return class_exists($class, FALSE);
	}
	
	/**
	 * Creates and returns an instance of the given class.
	 * 
	 * @param string $class The class name including the namespace
	 * @return object  The instance of the class, or NULL on error
	 */
	public function makeInstance($class){
		if($this->loadClassFile($class)){
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
	public function getValueForKeyPath($identifier){
		$command = "get $identifier";
		$this->sendCommand($command);
		return $this->waitForResponse();
	}
	/**
	 * @see getValueForKeyPath()
	 */
	public function getValueForKey($identifier){
		return $this->getValueForKeyPath($identifier);
	}
	
	/**
	 * Sets the new value for the identifier of the POP server.
	 * 
	 * @param string $identifier The identifier of the value to set
	 * @param object $value The new value to set
	 * @return void
	 */
	public function setValueForKeyPath($identifier, $value){
		$value = $this->getCommandStringForValue($value);
		$command = "set $identifier $value";
		$this->sendCommand($command);
	}
	/**
	 * @see setValueForKeyPath()
	 */
	public function setValueForKey($identifier, $value){
		$this->setValueForKeyPath($identifier, $value);
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
	
    /**
	 * Sends the given command to the POP server.
	 * 
	 * @param string $command The command to send
	 * @return void
	 */
	public function sendCommand($command){
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
	
	
	
	/* MWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWM */
	/* HELPERS           MWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWM */
	/* MWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWMWM */
	/**
	 * Returs the command string for the given value.
	 * 
	 * @param mixed $value Description
	 * @return string  The string representation of the value
	 */
	public function getCommandStringForValue($value){
		$result = '';
		if(is_string($value)){
			$result = $this->prepareString($value);
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
	protected function prepareString($string){
        $string = str_replace(' ', '&_', $string);
        return '@"' . $string . '"';
	}
}
    
?>