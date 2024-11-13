<?php
namespace controller;


class AuthCtrl extends Ctrl
{
	
	public static function beforeroute(\Base $f3, array $url, string $controler)
	{
		parent::beforeRoute($f3, $url, $controler);
	}
	
	
	public static function afterroute(\Base $f3, array $url, string $controler)
	{
		
		parent::afterRoute($f3, $url, $controler);
	}
	
	
	public static function GET_login (\Base $f3, array $url, string $controler)
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
	
	public static function POST_login (\Base $f3, array $url, string $controler)
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
	
	
	public static function GET_logout (\Base $f3, array $url, string $controler)
	{
		$f3 = \Base::instance();
		
		$f3->clear("SESSION.auth_user");
		$f3->reroute("/login");
	}
	
}
