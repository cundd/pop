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
		//$this->sendCommand('window close');
        
		// new NSWindow myWin 1;
		// myWin initWithContentRect:styleMask:backing:defer: @NSMakeRect(0,0,200,200) (uint)13 (uint)2 (int)1;
		
        // $window = Runtime::makeInstance('NSWindow');
        $window = new \NSWindow();
		$window->initWithContentRect_styleMask_backing_defer('@NSMakeRect(0,200,800,600)', uint(13), uint(2), (int)1);
        
        $drawView = new \NSView();
        $drawView->initWithFrame(new \Qoq\Rect(0, 0, 200, 300));
		$window->getContentView()->addSubview($drawView);
		$window->makeKeyAndOrderFront(nil());
        
		
		Runtime::pd($drawView->getValueForKey('canDraw'));
		
        $drawView->lockFocusIfCanDraw(); // [(NSView *)drawView lockFocus];
        
        /*
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
		// $webView->getMainFrame()->loadRequest($request);
		
		#$mainBundle = new \NSBundle();
		#$mainBundle = $mainBundle->mainBundle();
		#$mainBundle->loadNibNamed_owner_options(string('About'), nil(), nil());
		
        $bundle = \NSBundle::mainBundle();
		\NSBundle::loadNibNamed_owner(string("About.xib"), nil());
		Runtime::pd($bundle);
		$webView->reload(nil());
        */

        
        
        $path = new \NSBezierPath(TRUE); 						// [NSBezierPath bezierPath];
		$path->init();
		Runtime::pd($path);
		//Runtime::breakpoint($path);
        $path->setLineWidth(4); 								// [path setLineWidth:4];
        
        $center = new \Qoq\Point(128, 128); 					// NSPoint center = { 128,128 };
        
		
		//Runtime::breakpoint($path);
        $path->moveToPoint($center);                            // [path moveToPoint: center];
		
        //	[path appendBezierPathWithArcWithCenter: center
		//									 radius: 64
		//								 startAngle: 0
		//								   endAngle: 321];
		$path->appendBezierPathWithArcWithCenter_radius_startAngle_endAngle($center,
																			64,
																			0,
																			321);
		
		\NSColor::whiteColor()->set(); 							// [[NSColor whiteColor] set];
		$path->fill();											// [path fill];
        
        \NSColor::grayColor()->set(); 							// [[NSColor grayColor] set];
        $path->stroke(); 										// [path stroke];
		
		$drawView->setNeedsDisplay(TRUE);
		$window->getContentView()->setNeedsDisplay(TRUE);
		Runtime::breakpoint($path);
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