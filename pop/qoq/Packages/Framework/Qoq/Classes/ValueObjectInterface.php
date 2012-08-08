<?php
namespace Qoq;

/*
 * @license
 */

/**
 * The interface for value objects.
 */
interface ValueObjectInterface {
   /**
	* Returns the string representation of the value and the argument that will
	* be sent if the object is passed to POP.
	* 
    * @return string
    */
	public function __toString();
}

?>