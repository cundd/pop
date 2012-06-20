<?php
namespace Qoq\Controller;

/*
 * @license
 */



abstract class AbstractActionController extends AbstractController {
	/**
	 * Handles the given signal.
	 * 
	 * @param string $signal The signal received from POP
	 * @param string $arg1 The first of n arguments
	 * @return void
	 */
    public function handle($signal){
		$arguments = func_get_args();
		$signal = $this->convertSignalToMethodName($signal);
		if(method_exists($this, $signal)){
			call_user_func_array(array($this, $signal), $arguments);
		} else {
			call_user_func_array(array($this, 'errorAction'), $arguments);
		}
	}
	
	/**
	 * The default error action.
	 *
	 * This method is invoked if the controller has no method with the name
	 * given in $command.
	 * 
	 * @param string $command The command, that this controller doesn't implement
	 * @return void
	 */
	public function errorAction($signal){
		$this->sendCommand('# The controller ' . get_class($this) . ' doesn\' respond to ' . $signal);
	}
}

?>