<?php
namespace Qoq\Helpers;

/*
 * @license
 */



class ObjectHelper {
	/**
	 * Returns the value for the given key path of the given object.
	 * 
	 * @param string $keyPath The key path of the value to get
	 * @param object $object The object to get the value of
	 * @param string $lastKeyPathSegment Reference to the last key path segment
	 * @return object  The value for the key path
	 */
	static public function getValueForKeyPathOfObject($keyPath, $object, &$lastKeyPathSegment = NULL) {
		$pathSegments = explode('.', $keyPath);
		while ($key = current($pathSegments)) {
			$object = self::getValueForKeyOfObject($key, $object);
			next($pathSegments);
		}
		
		$lastKeyPathSegment = end($pathSegments);
		return $object;
	}
	
	
	/**
	 * Returns the value for the given key of the given object.
	 * 
	 * @param string $key The key of the value to get
	 * @param object $object The object to get the value of
	 * @return object  The value for the key
	 */
	static public function getValueForKeyOfObject($key, $object) {
		$value = NULL;
		$accessorMethod = 'get' . ucfirst($key);
		
		// Has accessor method
		if (method_exists($object, $accessorMethod)) {
			$value = $object->$accessorMethod();
		} else
		// Direct access
		if (property_exists($object, $key)) {
			$value = $object->$key;
		} else
		// Is array and key exists
		if (is_array($object) && isset($object[$key])) {
			$value = $object[$key];
		} else
		// Is traversable
		if ($object instanceof Traversable && isset($object[$key])) {
			$value = $object[$key];
		}
		return $value;
	}
	
	/**
	 * Sets the new value for the given key path.
	 * 
	 * @param string $keyPath The key path of the value to get
	 * @param object $value The new value to set
	 * @param object $object The object to change
	 * @return void
	 */
	static public function setValueForKeyPathOfObject($keyPath, $value, $object) {
		$lastKeyPathSegment = '';
		$object = self::getValueForKeyPathOfObject($keyPath, $object, $lastKeyPathSegment);
		return self::setValueForKeyOfObject($lastKeyPathSegment, $value, $object);
	}
	
	/**
	 * Sets the new value for the given key of the given object.
	 * 
	 * @param string $keyPath The key the set
	 * @param object $value The new value to set
	 * @param object $object The object to change
	 * @return void
	 */
	static public function setValueForKeyOfObject($key, $value, $object) {
		$accessorMethod = 'set' . ucfirst($key);
		
		// Has accessor method
		if (method_exists($object, $accessorMethod)) {
			$object->$accessorMethod($value);
		} else
		// Direct access
		if (is_object($object) && property_exists($object, $key)) {
			$object->$key = $value;
		} else
		// Is array or Traversable
		if (is_array($object) || $object instanceof Traversable) {
			$object[$key] = $value;
		} else {
			return FALSE;
		}
		return TRUE;
	}
}

?>