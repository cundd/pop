<?php
namespace Qoq;

/*
 * @license
 */

/**
 * The uint wrapper object.
 */
class Uint extends ValueObject {
   /**
	* Returns the string representation of the value and the argument that will
	* be sent if the object is passed to POP.
	* 
    * @return string
    */
   public function __toString(){
	   return '(uint)' . $this->value;
   }
}

?>