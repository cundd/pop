<?php
namespace Qoq;

/*
 * @license
 */

/**
 * The size wrapper object.
 */
class Size implements ValueObjectInterface {
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
	 * @param	float	$width	The width
	 * @param	float	$height	The height
	 * @return	Size			Returns the new size
	 */
	public function __construct($width, $height) {
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
		return'@NSMakeSize(' . $this->width . ',' . $this->height . ')';
	}
}

?>