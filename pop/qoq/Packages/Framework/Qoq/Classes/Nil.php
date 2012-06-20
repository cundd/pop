<?php
namespace Qoq;

/*
 * @license
 */

/**
 * The nil object.
 */
class Nil {
	/**
	 * Returns nil
	 * 
	 * @param string $name The name of the method
	 * @param array $arguments Arguments sent to the method
	 * @return mixed
	 */
	public function __call($name, $arguments){
		return $this;
	}
	
	/**
	 * Returns nil
	 * 
	 * @param string $name The name of the method
	 * @param array $arguments Arguments sent to the method
	 * @return mixed
	 */
	static public function __callStatic($name, $arguments){
		return $this;
	}
	
	public function __invoke($x){
		return FALSE;
	}
	
	public function __toString(){
		return '';
	}
	
	public function __get($key){
		return $this;
	}
	
	public function __set($key, $value){
		
	}
	
	public function __isset($key){
		return FALSE;
	}
	
	public function __unset($key){
		
	}
	
	/**
	 * Returns the shared nil instance.
	 * 
	 * @return Nil
	 */
	static public function makeInstance(){
		return self::nil();
	}
	/**
	 * @see makeInstance()
	 */
	static public function nil(){
		static $instance;
		if(!$instance){
			$instance = new static();
		}
		return $instance;
	}
}

?>