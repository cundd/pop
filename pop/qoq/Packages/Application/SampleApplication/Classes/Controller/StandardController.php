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
		echo 'window setTitle: @"Hallo&_wie&_geht&_es&_dir?";';
	}
}

?>