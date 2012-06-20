<?php
namespace SampleApplication\Controller;

/*
 * @license
 */

use \Qoq\QoqRuntime as Runtime;
use \Qoq\Controller\AbstractActionController;

class StandardController extends AbstractActionController {
    /**
     * Invoked when the button in the first window is pressed.
     *
     * @param string $command The received command
     * @param return void
     */
	public function loadNibAction($command){
		$this->sendCommand('window close');
        
		// new NSWindow myWin 1;
		// myWin initWithContentRect:styleMask:backing:defer: @NSMakeRect(0,0,200,200) (uint)13 (uint)2 (int)1;
		
		$window = Runtime::makeInstance('NSWindow', TRUE);
		$window->initWithContentRect_styleMask_backing_defer('@NSMakeRect(0,200,800,600)', uint(13), uint(2), (int)1);
		$window->makeKeyAndOrderFront(nil());
    }
}

?>