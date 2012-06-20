<?php
namespace Qoq;

/*
 * @license
 */

/**
 * The int wrapper object.
 */
class Int extends ValueObject {
   /**
	* Returns the string representation of the value and the argument that will
	* be sent if the object is passed to POP.
	* 
    * @return string
    */
   public function __toString(){
	   return '(int)' . $this->value;
   }
}

?>