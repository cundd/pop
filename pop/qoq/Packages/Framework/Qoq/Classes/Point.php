<?php
namespace Qoq;

/*
 * @license
 */

/**
 * The point wrapper object.
 */
class Point implements ValueObjectInterface {
	/**
	 * The X coordinate.
	 * 
	 * @var float
	 */
	protected $x = 0.0;
	
	/**
	 * The Y coordinate.
	 * 
	 * @var float
	 */
	protected $y = 0.0;
	
	/**
	 * Creates a new instance with the given coordinates.
	 * 
	 * @param	float	$x	The X coordinate
	 * @param	float	$y	The Y coordinate
	 * @return	Point		Returns the new point
	 */
	public function __construct($x, $y) {
		$this->x = $x;
		$this->y = $y;
		return $this;
	}
	
	/**
	 * Returns the string representation of the value and the argument that will
	 * be sent if the object is passed to POP.
	 * 
	 * @return string
	 */
	public function __toString() {
		return'@NSMakePoint(' . $this->x . ',' . $this->y . ')';
	}
}

?>