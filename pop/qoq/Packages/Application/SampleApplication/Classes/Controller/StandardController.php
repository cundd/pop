<?php
namespace SampleApplication\Controller;

/*
 * @license
 */

use \Qoq\Controller\AbstractActionController;

class StandardController extends AbstractActionController {
    /**
     * Invoked when the button in the first window is pressed.
     *
     * @param string $command The received command
     * @param return void
     */
	public function loadNibAction($command){
		echo '#' . exec('ps -l');
		$this->sendCommand('window close');
        $this->sendCommand("NSBundle loadNibNamed:owner: @'secondWindow' self");
    }
}

?>