<?php
namespace Qoq;

/*
 * @license
 */

/**
 * An abstract class to represent basic values.
 */
class ValueObject {
   /**
	* The represented value.
	* 
	* @var mixed
	*/
   protected $value = nil;
   
   /**
	* Sets the given value as value of the object.
	* 
	* @param mixed $value
    * @return ValueObject
    */
   public function __construct($value){
	  $this->value = $value;
	  return $this;
   }
   
   /**
	* Returns the string representation of the value and the argument that will
	* be sent if the object is passed to POP.
	* 
    * @return string
    */
   public function __toString(){
	   return '' . $this->value;
   }
}

?>