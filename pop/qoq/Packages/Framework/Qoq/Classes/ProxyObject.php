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
	 * @param string $className The name of the represented class.
	 * @return object
	 */
	public function __construct($className) {
		$this->_className = $className;
		$this->_uuid = 'inst-' . $className . '-' . time();
		return $this;
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
	 * Set the data received from POP.
	 *
	 * @param mixed
	 *
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
		if ($prefix === 'set') {
			return $this->setValueForKeyPath($property, $arguments[0]);
		} else if ($prefix === 'get') {
			return $this->getValueForKeyPath($property);
		}
		
		$command = Runtime::convertMethodNameToCommand($this, $name, $arguments);
		return Runtime::sendCommand($command);
	}
}

?>