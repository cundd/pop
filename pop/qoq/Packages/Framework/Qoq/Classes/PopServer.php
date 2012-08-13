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
class PopServer extends ProxyObject {
	/**
	 * The shared PopServer proxy object.
	 * 
	 * @var PopServer
	 */
	static protected $popServerInstance;
	
	/**
	 * Returns the PopServer instance as a proxy object for a object of the POP server.
	 * 
	 * @param string|array $className The name of the represented class, or an array of arguments
	 * @return object
	 */
	public function __construct($className = '') {
		if (self::$popServerInstance) {
			throw new \RuntimeException('An instance of PopServer already exists.', 1344504743);
		}
		$this->_className = 'PopServer';
		$this->_uuid = 'self';
		return $this;
	}
	
	/**
	 * Returns the shared PopServer proxy object, or creates a new one, if it
	 * doesn't exist.
	 * 
	 * @return	PopServer
	 */
	public function sharedInstance() {
		if (!self::$popServerInstance) {
			self::$popServerInstance = new PopServer();
		}
		return self::$popServerInstance;
	}
}

?>