<?php
namespace Qoq;

/*
 * @license
 */

use Qoq\QoqRuntime as Runtime;
use Qoq\Helpers\ObjectHelper as ObjectHelper;

/**
 * The proxy object builds the representation of an Objective-C object in the
 * QOQ environment.
 */
class ProxyObject {
	/**
	 * The unique identifier of the object.
	 * 
	 * @var string
	 */
	protected $_uuid = NULL;
	
	/**
	 * The name of the represented class.
	 * 
	 * @var string
	 */
	protected $_className = '';
	
	/**
	 * The importer object that translates the data from POP into PHP values.
	 * 
	 * @var object
	 */
	protected $_importer = NULL;
	
	/**
	 * The data received from POP.
	 * 
	 * @var mixed
	 */
	protected $_data = NULL;
	
	/**
	 * Returns a new proxy object for a object of the POP server.
	 * 
	 * @param string|array $className The name of the represented class, or an array of arguments
	 * @return object
	 */
	public function __construct($className = '') {
		$arguments = func_get_args();
		
		/*
		 * If the class name of this is the Proxy Object, the first argument has
		 * to be the Objective-C class name.
		 */
		$className = get_class($this);
		if ($className === __CLASS__) {
			$className = array_shift($arguments);
		}
		$this->_className = $className;
		$this->_uuid = 'inst-' . $className . '-' . time();
		
		if (!in_array('>dontSend', $arguments, TRUE)) {
			$this->createObjectInPop($arguments);
		}
		
		return $this;
	}
	
	/**
	 * Creates the object in the POP server space.
	 *
	 * @param array<string>	$arguments The arguments
	 * @return void
	 */
	protected function createObjectInPop($arguments) {
		$popData = NULL;
		$identifier = $this->getUuid();
		Runtime::sendCommand('new ' . $this->_className . ' ' . $identifier . ' ' . implode(' ', $arguments));
		
		/*
		 * Some Objective-C objects (i.e. NSURL) can not be retrieved until they
		 * are initialized.
		 * If you want the data to be fetched automatically, you can pass
		 * ">retrievePopData" as one of the arguments.
		 */
		if (in_array('>retrievePopData', $arguments)) {
			$popData = Runtime::getValueForKeyPath($identifier, TRUE);
			$this->setData($popData);
		}
	}
	
	/**
	 * Returns the unique identifier of the object.
	 * 
	 * @return string
	 */
	public function getUuid() {
		return $this->_uuid;
	}
	
	/**
	 * Sets the unique identifier of the object.
	 *
	 * @param string $uuid The new unique identifier
	 * @return void
	 */
	public function setUuid($uuid) {
		$this->_uuid = $uuid;
	}
	
	/**
	 * Returns the value for the given key path.
	 * 
	 * @param string $keyPath The key path of the value to get
	 * @return object  The value for the key path
	 */
	public function getValueForKeyPath($keyPath) {
		$value = ObjectHelper::getValueForKeyPathOfObject($keyPath, $this);
		if (!$value) {
			$value = Runtime::getValueForKeyPath($this->getUuid() . '.' . $keyPath);
		}
		return $value;
	}
	/**
	 * @see getValueForKeyPath()
	 */
	public function getValueForKey($keyPath) {
		return $this->getValueForKeyPath($keyPath);
	}
	
	/**
	 * Returns the value for the given key.
	 * 
	 * @param	string	$name	The property key
	 * @return	mixed			The property's value
	 */
	public function __get($name) {
		return $this->getValueForKey($name);
	}
	
	/**
	 * Sets the new value for the given key path.
	 * 
	 * @param string $keyPath The key path of the value to get
	 * @param object $value The new value to set
	 * @return void
	 */
	public function setValueForKeyPath($keyPath, $value) {
		if (!ObjectHelper::setValueForKeyPathOfObject($keyPath, $value, $this)) {
			Runtime::setValueForKeyPath($this->getUuid() . '.' . $keyPath, $value);
		}
	}
	/**
	 * @see setValueForKeyPath()
	 */
	public function setValueForKey($keyPath, $value) {
		return $this->setValueForKeyPath($keyPath, $value);
	}
	
	/**
	 * Sets the value for the given key.
	 * 
	 * @param	string	$name	The property key
	 * @param	mixed	$value	The new value to set
	 * @return	void
	 */
	public function __set($name, $value) {
		$this->setValueForKey($name, $value);
	}
	
	/**
	 * Returns the data received from POP.
	 *
	 * @param mixed
	 * @return void
	 */
	public function getData() {
		if (!$this->_data) {
			$popData = Runtime::getValueForKeyPath($identifier, TRUE);
			$this->setData($popData);
		}
		return $this->_data;
	}
	
	/**
	 * Set the data received from POP.
	 *
	 * @param mixed
	 * @return void
	 */
	public function setData($value) {
		$this->_data = $value;
	}
	
	/**
	 * Returns the unique identifier of the object when converted to a string.
	 * 
	 * @return string
	 */
	public function __toString() {
		return $this->getUuid();
	}
	
	/**
	 * Tries to dynamically resolve methods.
	 *
	 * If the method name starts with 'set' setValueForKey() will be called.
	 * If the method name starts with 'get' getValueForKey() will be called.
	 * All other method names will be parsed with convertMethodNameToCommand().
	 * 
	 * @param string $name The name of the method
	 * @param array $arguments Arguments sent to the method
	 * @return mixed
	 */
	public function __call($name, $arguments) {
		$prefix = substr($name, 0, 3);
		$property = lcfirst(substr($name, 3));
		//if ($prefix === 'set') {
		//	return $this->setValueForKeyPath($property, $arguments[0]);
		//} else
		if ($prefix === 'get') {
			return $this->getValueForKeyPath($property);
		}
		
		$command = Runtime::convertMethodNameToCommand($this, $name, $arguments);
		return Runtime::sendCommand($command);
	}
	
	/**
	 * Tries to dynamically resolve methods.
	 *
	 * If the method name starts with 'set' setValueForKey() will be called.
	 * If the method name starts with 'get' getValueForKey() will be called.
	 * All other method names will be parsed with convertMethodNameToCommand().
	 * 
	 * @param string $name The name of the method
	 * @param array $arguments Arguments sent to the method
	 * @return mixed
	 */
	static public function __callStatic($name, $arguments) {
		$className = get_called_class();
		$command = Runtime::convertMethodNameToCommand($className, $name, $arguments);
		Runtime::sendCommand($command);
		$response = Runtime::sharedInstance()->waitForResponse();
		if (trim($response)) {
			return $response;
		}
		return Runtime::getValueForKeyPath('classObj-' . $className . '-' . $name);
	}
}

?>