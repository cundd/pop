<?php
namespace Qoq;

/*
 * @license
 */

/**
 * The rect wrapper object.
 */
class Rect implements ValueObjectInterface {
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
	 * The width.
	 * 
	 * @var float
	 */
	protected $width = 0.0;
	
	/**
	 * The height.
	 * 
	 * @var float
	 */
	protected $height = 0.0;
	
	/**
	 * Creates a new instance with the given width and height.
	 * 
	 * @param	float	$x		The X coordinate
	 * @param	float	$y		The Y coordinate
	 * @param	float	$width	The width
	 * @param	float	$height	The height
	 * @return	Size			Returns the new size
	 */
	public function __construct($x, $y, $width, $height) {
		$this->x = $x;
		$this->y = $y;
		$this->width = $width;
		$this->height = $height;
		return $this;
	}
	
	/**
	 * Returns the string representation of the value and the argument that will
	 * be sent if the object is passed to POP.
	 * 
	 * @return string
	 */
	public function __toString() {
		return'@NSMakeRect(' . $this->x . ',' . $this->y . ',' . $this->width . ',' . $this->height . ')';
	}
}

?>