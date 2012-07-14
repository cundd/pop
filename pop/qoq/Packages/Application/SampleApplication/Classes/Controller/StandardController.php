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
		
        // $window = Runtime::makeInstance('NSWindow');
        $window = new \NSWindow();
		$window->initWithContentRect_styleMask_backing_defer('@NSMakeRect(0,200,800,600)', uint(13), uint(2), (int)1);
        
		$webView = Runtime::makeInstance('WebView');
		$webView->initWithFrame_frameName_groupName('@NSMakeRect(0,0,800,600)', '@MainFrame', '@MainScope');
        
		$window->getContentView()->addSubview($webView);
        $window->makeKeyAndOrderFront(nil());
        
		$urlString = string("http://www.cundd.net");
	
		$url = new \NSURL();
		$url->initWithString($urlString);
		
		$request = new \NSURLRequest();
		$request->initWithURL($url);
		
		$webView->getValueForKey('mainFrame')->loadRequest($request);
		
		#$mainBundle = new \NSBundle();
		#$mainBundle = $mainBundle->mainBundle();
		#$mainBundle->loadNibNamed_owner_options(string('About'), nil(), nil());
		
        $bundle = \NSBundle::mainBundle();
		\NSBundle::loadNibNamed_owner(string("About.xib"), nil());
		Runtime::pd($bundle);
		
		/*
        #Runtime::makeInstance('NSWindow', TRUE);
        #        initWithString
        $urlProvider = new ProxyObject(string("http://www.google.com"));
        $webView->takeStringURLFrom($urlProvider);
		*/
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