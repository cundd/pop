<?php
namespace SampleApplication\Controller;

/*
 * @license
 */

use \Qoq\QoqRuntime as Runtime;
use \Qoq\ProxyObject as ProxyObject;
use \Qoq\Controller\AbstractActionController;

class StandardController extends AbstractActionController {
	/**
	 * Called when the application started.
	 * 
	 * @return void
	 */
	public function applicationDidFinishLaunching() {
		$this->sendCommand('window close');
        
		// new NSWindow myWin 1;
		// myWin initWithContentRect:styleMask:backing:defer: @NSMakeRect(0,0,200,200) (uint)13 (uint)2 (int)1;
		
		$window = Runtime::makeInstance('NSWindow', TRUE);
		$window->initWithContentRect_styleMask_backing_defer('@NSMakeRect(0,200,800,600)', uint(13), uint(2), (int)1);
        
		$webView = Runtime::makeInstance('WebView', TRUE);
		$webView->initWithFrame_frameName_groupName('@NSMakeRect(0,200,800,600)', '@MainFrame', '@MainScope');
                
		$window->getContentView()->addSubview($webView);
        $window->makeKeyAndOrderFront(nil());
        
		#new NSURL();
        #Runtime::makeInstance('NSWindow', TRUE);
        #        initWithString
        $urlProvider = new ProxyObject(string("http://www.google.com"));
        $webView->takeStringURLFrom($urlProvider);
        
        $webView->reload(nil());
	}
	
    /**
     * Invoked when the button in the first window is pressed.
     *
     * @param string $command The received command
     * @param return void
     */
	public function loadNibAction($command) {
    }
}

?>