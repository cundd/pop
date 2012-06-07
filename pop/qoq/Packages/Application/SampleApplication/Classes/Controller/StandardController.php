<?php
namespace SampleApplication\Controller;

/*
 * @license
 */

use \Qoq\Controller\AbstractController;

class StandardController extends AbstractController {
	/**
	 * Invoke the command.
	 * 
	 * @param string $command The command received from POP
	 * @return void
	 */
	public function handle($command){
        $oldTitle = $this->getValueForKeyPath('window.title');
        $oldTitle = \Qoq\QoqRuntime::escapeString($oldTitle);
		echo 'window setTitle: @"This&_has&_been&_the&_old&_title:&_' . $oldTitle . '";';
	}
}

?>