<?php
namespace controller;

use Session;


abstract class Ctrl
{
	
	public static function beforeRoute (\Base $f3, array $url, string $controler)
	{
		// exposes $f3 var in views
		$f3->set("f3", $f3);

		// initialize sessions
		new Session();
	}
	
	
	public static function afterRoute (\Base $f3, array $url, string $controler)
	{
		
	}
	
}