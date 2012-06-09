<?php
namespace Qoq\Controller;

/*
 * @license
 */



abstract class AbstractActionController extends AbstractController {
	/**
	 * Handles the given command.
	 * 
	 * @param string $command The command received from POP
	 * @param string $arg1 The first of n arguments
	 * @return void
	 */
    public function handle($command){
		$arguments = func_get_args();
		$command = $this->convertCommandToMethodName($command);
		if(method_exists($this, $command)){
			call_user_func_array(array($this, $command), $arguments);
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
	public function errorAction($command){
		$this->sendCommand('# The controller ' . get_class($this) . ' doesn\' respond to ' . $command);
	}
}

?>