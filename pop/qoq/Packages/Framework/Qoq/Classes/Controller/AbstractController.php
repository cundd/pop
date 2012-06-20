<?php
namespace Qoq\Controller;

/*
 * @license
 */



abstract class AbstractController {
	/**
	 * The parts of the command that has been received from POP.
	 * 
	 * @var array<string>
	 */
	protected $commandParts = array();
	
	/**
	 * Handles the given signal.
	 * 
	 * @param string $signal The signal received from POP
	 * @param string $arg1 The first of n arguments
	 * @return void
	 */
	abstract public function handle($signal);
	
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
	
	/**
	 * Sends the given command to the POP server.
	 * 
	 * @param string $command The command to send
	 * @return void
	 */
	public function sendCommand($command){
		return \Qoq\QoqRuntime::sendCommand($command);
	}
	
	/**
	 * Converts the command to a method name.
	 *
	 * @example:
	 * Converts
	 *  loadNibNamed:owner:
	 *
	 * into
	 *  loadNibNamedOwner
	 *
	 * @param string $command The command to convert
	 * @return string  Returns the converted method name
	 */
	public function convertSignalToMethodName($signal){
		$signal = trim($signal);
		
		/*
		 * If the command is "exec" read the command from the original command
		 * parts
		 */
		if($signal == 'exec'){
			$commandParts = $this->getCommandParts();
			$signal = $commandParts[2]; // The element at 1 is the sender
		}
		
		// Remove the colons from the command
		if(strpos($signal, ':')){
			// Split the command string into words
			$words = explode(':', strtolower($signal));
			
			$signal = '';
			foreach ($words as $word) {
				$signal .= ucfirst(trim($word));
			}
		}
		
		return $signal;
	}
	
	/**
	 * Returns the parts of the command that has been received from POP.
	 *
	 * @return array<string>
	 */
	public function getCommandParts() {
		return $this->commandParts;
	}
	
	/**
	 * Setter for the parts of the command that has been received from POP.
	 *
	 * @param array<string>
	 *
	 * @return void
	 */
	public function setCommandParts($value) {
		$this->commandParts = $value;
	}
}

?>