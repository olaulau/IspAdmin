<?php
namespace controller;

use model\DnsInfos;

class FrontCtrl
{
	
	public static function beforeroute()
	{
		$f3 = \Base::instance();
		
		if(empty( $f3->get("SESSION.auth_user") )) {
			$f3->reroute("/login");
		}
	}
	
	
	public static function afterroute()
	{
		
	}
	
	
	public static function GET_index ()
	{
		$f3 = \Base::instance();
		
		$PAGE = [
				"name" => "index",
				"title" => "Isp Admin",
		];
		$f3->set("PAGE", $PAGE);
		
		$view = new \View();
		echo $view->render('index.phtml');
	}
	
	
	public static function GET_websites ()
	{
		$generation_start = microtime(true);
		
		$f3 = \Base::instance();
		
		// get servers and websites list
		$cache = \Cache::instance();
		$key = "ispconfig";
		if ($cache->exists($key, $ispconfigRawinfos) === false) { //TODO count in stats
			$ispconfigRawinfos = \IspConfig::IspGetInfos ();
			$cache->set($key, $ispconfigRawinfos, $f3->get("cache.ispconfig"));
		}
		list($servers, $websites, $phps) = $ispconfigRawinfos;

		if(!empty($f3->get('debug.websites_filter'))) {
			array_walk ( $websites , function ( $value , $key , $filter ) use ( &$websites ) {
				if (strpos($key, $filter) === false) {
					unset ($websites[$key]); // dev test by filtering domain
				}
			} , $f3->get('debug.websites_filter') );
		}
		
		if(!empty($f3->get('debug.websites_max_number'))) {
			$websites = array_slice($websites, 0, $f3->get('debug.websites_max_number')); // dev test with few domains
		}
// 		var_dump($websites); die;
		
		// group domains by 2LD
		$stats = [];
		$stats["websites_count"] = count($websites);
		$websites = group2dArray($websites, "2LD");
		$stats["2LD_count"] = count($websites);
// 		var_dump($websites); die;
		
		// get infos (by running external processes)
		$cmds = [];
		$tasks = [];
		foreach ($websites as $parent => $group) {
			foreach ($group as $domain => $website) {
				if ($website["ispconfigInfos"]['active']==='y') {
					$server = $servers[$website["ispconfigInfos"]["server_id"]];
					if ($f3->get('active_modules.whois') === true) {
						$t = new \model\WhoisInfos ($parent, $server);
						$tasks["whois"][$parent] = $t;
						$cmds["whois_$parent"] = $t->getCmd();
						//TODO do not recreate things if parents domain has already been done
					}
					if ($f3->get('active_modules.dns') === true) {
						$t = new \model\DnsInfos ($domain, $server);
						$tasks["dns"][$domain] = $t;
						$cmds["dns_$domain"] = $t->getCmd();
					}
					if ($f3->get('active_modules.ssl') === true) {
						$t = new \model\SslInfos ($domain, $server);
						$tasks["ssl"][$domain] = $t;
						$cmds["ssl_$domain"] = $t->getCmd();
					}
					if ($f3->get('active_modules.http') === true) {
						$t = new \model\HttpInfos ($domain, $server);
						$tasks["http"][$domain] = $t;
						$cmds["http_$domain"] = $t->getCmd();
					}
					if ($f3->get('active_modules.php') === true) {
						$t = new \model\PhpInfos ($domain, $server, $website, $phps);
						$tasks["php"][$domain] = $t;
					}
				}
			}
		}
// 		vdd($cmds);
		
		$stats['total_cmds'] = count($cmds);
		execMultipleProcesses($cmds, true, true);
		$stats['total_executed_cmds'] = count($cmds);
		$stats['executed_cmds'] = $cmds;
// 		vdd($cmds);
		
		// extract infos
		foreach ($websites as $parent => &$group) {
			foreach ($group as $domain => &$website) {
				if ($website["ispconfigInfos"]['active']==='y') {
					$server = $servers[$website["ispconfigInfos"]["server_id"]];
					
				    if ($f3->get('active_modules.whois') === true) {
				    	$tasks["whois"][$parent]->extractInfos($website['ispconfigInfos']);
				    	$website['whoisInfos'] = $tasks["whois"][$parent];
				    }
				    
					if ($f3->get('active_modules.dns') === true) {
						$tasks["dns"][$domain]->extractInfos($website['ispconfigInfos']);
					     $website['dnsInfos'] = $tasks["dns"][$domain];
					}
					
					if ($f3->get('active_modules.ssl') === true) {
						$tasks["ssl"][$domain]->extractInfos($website['ispconfigInfos']);
						$website['sslInfos'] = $tasks["ssl"][$domain];
					}
					if ($f3->get('active_modules.http') === true) {
						$tasks["http"][$domain]->extractInfos($website['ispconfigInfos']);
						$website['httpInfos'] = $tasks["http"][$domain];
					}
					if ($f3->get('active_modules.php') === true) {
						$tasks["php"][$domain]->extractInfos($website['ispconfigInfos']);
						$website['phpInfos'] = $tasks["php"][$domain];
					}
				}
				unset($website);
			}
			unset($group);
		}

// 		var_dump($websites); die;
		$f3->set('websites', $websites);
		
		$generation_end = microtime(true);
		$generation_time = number_format ( (($generation_end - $generation_start) * 1000 ), 0 , "," , " " ); // µs -> ms
		$footer_additional_text = '
				<span title="'.implode(PHP_EOL, $stats['executed_cmds']).'">'.$stats['total_executed_cmds'].' / '.$stats['total_cmds'].' executed</span> - 
				generated in '.$generation_time .' ms';
		$f3->set("footer_additional_text", $footer_additional_text);
		
		$PAGE = [
			"name" => "websites",
			"title" => "Web Sites",
		];
		$f3->set("PAGE", $PAGE);
		
		$view = new \View();
		echo $view->render('websites.phtml');
	}
	
	
	public static function GET_emails ()
	{
		$f3 = \Base::instance();
		
		$PAGE = [
				"name" => "emails",
				"title" => "E-mails",
		];
		$f3->set("PAGE", $PAGE);
		
		$view = new \View();
		echo $view->render('emails.phtml');
	}
	
	public static function POST_emails ()
	{
		$f3 = \Base::instance();
		$web = \Web::instance();
		
		// receive upload file
		$files = $web->receive(function($file, $formFieldName)
			{
				return true; // allows the file to be moved from php tmp dir to your defined upload dir
			},
			true);
		if(empty($files)) {
			die("no file uploaded");
		}
		
		// check fields
		if(empty($f3->get("POST.quota"))) {
			die("parameter problem");
		}
		
		$session_id = \IspConfig::IspLogin();
		$mail_domain_server_id = [];
		// uploaded file loop
		foreach ($files as $file => $uploaded) {
			if($uploaded === true) {
				// row loop
				$row = 1;
				if (($handle = fopen($file, "r")) !== FALSE) {
					while (($data = fgetcsv($handle)) !== FALSE) {
						list($email, $password) = $data;
						
						// delete mail user
						$mail_user = \IspConfig::IspGetMailUser($session_id, $email);
						if(!empty($mail_user)) {
							\IspConfig::IspDeleteMailUser($session_id, $mail_user["mailuser_id"]);
						}
						
						// calculate domain
						if(filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
							die("invalid email : $email");
						}
						$domain = explode("@", $email)[1];

						// look for server hosting mail domain
						if(empty($mail_domain_server_id[ $domain ])) {
							$mail_domain = \IspConfig::IspGetMailDomain($session_id, $domain);
							$mail_domain_server_id[ $domain ] = $mail_domain["server_id"];
						}
						
						// create mailbox
						$quota = $f3->get("POST.quota") * 1024 * 1024 * 1024; // GB -> Bytes
						$mail_user_id = \IspConfig::IspAddMailUser($session_id, $mail_domain_server_id[ $domain ], $email, $password, $quota);
						
						$row++;
					}
					fclose($handle);
					unlink($file);
				}
			}
		}
		
		$PAGE = [
				"name" => "emails",
				"title" => "E-mails",
		];
		$f3->set("PAGE", $PAGE);
		
		$view = new \View();
		echo $view->render('emails.phtml');
	}
	
}
