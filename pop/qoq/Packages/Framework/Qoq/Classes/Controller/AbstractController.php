<?php
namespace Qoq\Controller;

/*
 * @license
 */



abstract class AbstractController {
	/**
	 * Handles the given command.
	 * 
	 * @param string $command The command received from POP
	 * @return void
	 */
	abstract public function handle($command);
	
	/**
	 * Queries the POP server for the value for the given identifier.
	 * 
	 * @param string $identifier The identifier of the value to get
	 * @return object  The value for the identifier
	 */
	public function getValueForKeyPath($identifier){
		return \Qoq\QoqRuntime::getValueForKeyPath($identifier);
	}
	/**
	 * @see getValueForKeyPath()
	 */
	public function getValueForKey($identifier){
		return \Qoq\QoqRuntime::getValueForKeyPath($identifier);
	}
	
	/**
	 * Sets the new value for the identifier of the POP server.
	 * 
	 * @param string $identifier The identifier of the value to set
	 * @param object $value The new value to set
	 * @return void
	 */
	public function setValueForKeyPath($identifier, $value){
		return \Qoq\QoqRuntime::setValueForKeyPath($identifier, $value);
	}
	/**
	 * @see setValueForKeyPath()
	 */
	public function setValueForKey($identifier, $value){
		return \Qoq\QoqRuntime::setValueForKeyPath($identifier, $value);
	}
}

?>