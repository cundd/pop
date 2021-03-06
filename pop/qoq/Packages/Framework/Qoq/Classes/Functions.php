<?php

/**
 * This file contains functions.
 */

/**
 * Signal a value as a string.
 * 
 * @param mixed $value A string.
 * @return \Qoq\ValueObject  Returns a value object
 */
function string($value) {
	return new \Qoq\String($value);
}

/**
 * Signal a value as an integer.
 * 
 * @param mixed $value A numeric value.
 * @@return \Qoq\ValueObject  Returns a value object
 */
function int($value) {
	return new \Qoq\Int($value);
}

/**
 * Signal a value as an unsigned integer.
 * 
 * @param mixed $value A numeric value.
 * @return \Qoq\ValueObject  Returns a value object
 */
function uint($value) {
	return new \Qoq\Uint($value);
}

/**
 * Signal a value as a selector.
 * 
 * @param mixed $value A string value.
 * @return \Qoq\ValueObject  Returns a value object
 */
function sel($value) {
	return new \Qoq\Selector($value);
}
/**
 * @see sel()
 */
function selector($value) {
	return new \Qoq\Selector($value);
}

/**
 * Returns the shared nil instance.
 * 
 * @return Nil
 */
function nil() {
	return \Qoq\Nil::makeInstance();
}