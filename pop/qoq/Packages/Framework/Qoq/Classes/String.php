<?php
namespace Qoq;

/*
 * @license
 */

/**
 * The string wrapper object.
 */
class String extends ValueObject {
   /**
	* Returns the string representation of the value and the argument that will
	* be sent if the object is passed to POP.
	* 
    * @return string
    */
   public function __toString() {
	   return \Qoq\QoqRuntime::prepareString($this->value);
   }
}

?>