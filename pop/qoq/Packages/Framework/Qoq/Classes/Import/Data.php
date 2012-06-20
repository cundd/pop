<?php
namespace Qoq\Import;

/*
 * @license
 */

/**
 * The import data object holds data exported from POP.
 */
class Data {
	/**
	 * The importer object that translates the data from POP into PHP values.
	 * 
	 * @var object
	 */
	protected $importer = NULL;
	
	/**
	 * The data received from POP.
	 * 
	 * @var mixed
	 */
	protected $data = NULL;
	
	/**
	 * Returns the value for the given key path.
	 * 
	 * @param string $keyPath The key path of the value to get
	 * @return object  The value for the key path
	 */
	public function getValueForKeyPath($keyPath){
		$value = ObjectHelper::getValueForKeyPathOfObject($keyPath, $this);
		if(!$value){
			$value = \Qoq\QoqRuntime::getValueForKeyPath($keyPath);
		}
		return $value;
	}
	/**
	 * @see getValueForKeyPath()
	 */
	public function getValueForKey($keyPath){
		return $this->getValueForKeyPath($keyPath);
	}
	
	/**
	 * Sets the new value for the given key path.
	 * 
	 * @param string $keyPath The key path of the value to get
	 * @param object $value The new value to set
	 * @return void
	 */
	public function setValueForKeyPath($keyPath, $value){
		if(!ObjectHelper::setValueForKeyPathOfObject($keyPath, $value, $this)){
			\Qoq\QoqRuntime::setValueForKeyPath($keyPath, $value);
		}
	}
	/**
	 * @see setValueForKeyPath()
	 */
	public function setValueForKey($keyPath, $value){
		return $this->setValueForKeyPath($keyPath, $value);
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
	public function __call($name, $arguments){
		$prefix = substr($name, 0, 3);
		$property = lcfirst(substr($name, 3));
		if($prefix === 'set'){
			return $this->setValueForKeyPath($property, $arguments[0]);
		} else if($prefix === 'get'){
			return $this->getValueForKeyPath($property);
		}
		
		
	}
	
	
}

?>