<?php
namespace Qoq;

/*
 * @license
 */

/**
 * The selector wrapper object.
 */
class Selector extends ValueObject {
   /**
	* Returns the string representation of the value and the argument that will
	* be sent if the object is passed to POP.
	* 
    * @return string
    */
   public function __toString() {
	   return '(SEL)' . $this->value;
   }
}

?>