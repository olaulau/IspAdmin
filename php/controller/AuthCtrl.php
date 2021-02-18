<?php
namespace controller;

class AuthCtrl
{
	
	public static function beforeroute()
	{
		
	}
	
	
	public static function afterroute()
	{
		
	}
	
	
	public static function GET_login ()
	{
		$f3 = \Base::instance();
		
		$PAGE = [
				"name" => "login",
				"title" => "Log in",
		];
		$f3->set("PAGE", $PAGE);
		
		$view = new \View();
		echo $view->render('login.phtml');
	}
	
	public static function POST_login ()
	{
		$f3 = \Base::instance();
		
		if ($f3->get("POST.user") === $f3->get("tech.auth.user")  &&  $f3->get("POST.password") === $f3->get("tech.auth.password")) {
			$f3->set("SESSION.auth_user", $f3->get("tech.auth.user"));
			$f3->reroute("/");
		}
		else {
			$f3->reroute("/login");
		}
		
	}
	
	
	public static function GET_logout ()
	{
		$f3 = \Base::instance();
		
		$f3->clear("SESSION.auth_user");
		$f3->reroute("/login");
	}
	
}
