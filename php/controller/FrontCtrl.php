<?php
namespace controller;

use ErrorException;
use service\IspConfig;


class FrontCtrl extends Ctrl
{
	
	public static function beforeroute(\Base $f3, array $url, string $controler)
	{
		parent::beforeroute($f3, $url, $controler);
		
		if(empty( $f3->get("SESSION.auth_user") )) {
			$f3->reroute("/login");
		}
	}
	
	
	public static function afterroute(\Base $f3, array $url, string $controler)
	{
		
	}
	
	
	public static function homeGET ()
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
	
	public static function testGET ()
	{
		$f3 = \Base::instance();
		
		$PAGE = [
			"name" => "test",
			"title" => "test",
		];
		$f3->set("PAGE", $PAGE);
		
		$view = new \View();
		echo $view->render('test.phtml');
	}
	
	
	public static function websitesCheckGET ()
	{
		$generation_start = microtime(true);
		
		$f3 = \Base::instance();
		
		// get servers and websites list
		$cache = \Cache::instance();
		$key = "ispconfig";
		if ($cache->exists($key, $ispconfigRawinfos) === false) { //TODO count in stats
			$ispconfigRawinfos = IspConfig::IspGetInfos ();
			$cache->set($key, $ispconfigRawinfos, $f3->get("cache.ispconfig"));
		}
		global $servers;
		list($servers, $websites, $phps) = $ispconfigRawinfos;

		// filter domains (dev tests)
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
					if(!empty($servers[$website["ispconfigInfos"]["server_id"]])) {
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
		}
// 		vdd($cmds);
		
		$stats['total_cmds'] = count($cmds);
		execMultipleProcesses($cmds, true, true);
		$stats['total_executed_cmds'] = count($cmds);
		$stats['executed_cmds'] = $cmds;
		$f3->set('stats', $stats);
// 		vdd($cmds);
		
		// extract infos
		foreach ($websites as $parent => &$group) {
			foreach ($group as $domain => &$website) {
				if ($website["ispconfigInfos"]['active']==='y') {
					if(!empty($servers[$website["ispconfigInfos"]["server_id"]])) {
						$server = $servers[$website["ispconfigInfos"]["server_id"]]; //TODO useless
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
				}
				unset($website);
			}
			unset($group);
		}
// 		var_dump($websites); die;
		$f3->set('websites', $websites);
		
		$generation_end = microtime(true);
		$generation_time = number_format ( (($generation_end - $generation_start) * 1000 ), 0 , "," , " " ); // µs -> ms
		$footer_additional_text = ' | 
				<span title="'.implode(PHP_EOL, $stats['executed_cmds']).'">'.$stats['total_executed_cmds'].' / '.$stats['total_cmds'].' executed</span>
				 | 
				generated in '.$generation_time .' ms';
		$f3->set("footer_additional_text", $footer_additional_text);
		
		$PAGE = [
			"name" => "websites/check",
			"title" => "Web Sites",
		];
		$f3->set("PAGE", $PAGE);
		
		$view = new \View();
		echo $view->render('websites/check.phtml');
	}
	
	
	public static function emailsBulkGET ()
	{
		$f3 = \Base::instance();
		
		$PAGE = [
			"name" => "emails/bulk",
			"title" => "E-mails",
		];
		$f3->set("PAGE", $PAGE);
		
		$view = new \View();
		echo $view->render('emails/bulk.phtml');
	}
	
	
	public static function emailsBulkPOST ()
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
		if(empty($f3->get("POST.quota")) || !is_numeric($f3->get("POST.quota"))) {
			die("parameter problem");
		}
		$drop_existing_mailboxes = false;
		if( !empty($f3->get("POST.drop")) && $f3->get("POST.drop") === "on" ) {
			$drop_existing_mailboxes = true;
		}
		
		$session_id = IspConfig::IspLogin();
		$mail_domains = [];
		// uploaded file loop
		foreach ($files as $file => $uploaded) {
			if($uploaded === true) {
				// row loop
				$row = 1;
				if (($handle = fopen($file, "r")) !== FALSE) {
					while (($data = fgetcsv($handle)) !== FALSE) {
						list($email, $password) = $data;
						
						// delete mail user
						$mail_user = IspConfig::IspGetMailUser($session_id, $email);
						if(!empty($mail_user)) { // existing email
							if($drop_existing_mailboxes === true) {
								IspConfig::IspDeleteMailUser($session_id, $mail_user["mailuser_id"]);
							}
							else {
								continue; // can't recreate an existing mailbox
								//TODO count stats
							}
						}
						
						// calculate domain
						if(filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
							die("invalid email : $email");
						}
						list($email_username, $email_domain) = explode("@", $email);

						// look for server and client hosting mail domain (if not in cache)
						if(empty($mail_domains[$email_domain])) {
							$mail_domain = IspConfig::IspGetMailDomain($session_id, $email_domain);
							$client_id = IspConfig::IspGetClientIdFromUserId($session_id, $mail_domain["sys_groupid"]);
							$mail_domain["client_id"] = $client_id;
							$mail_domains[$email_domain] = $mail_domain;
						}
						
						// create mailbox
						$server_id = $mail_domains[$email_domain]["server_id"];
						$client_id = $mail_domains[$email_domain]["client_id"];
						$quota = $f3->get("POST.quota") * 1024 * 1024 * 1024; // GB -> Bytes
						$mail_user_id = IspConfig::IspAddMailUser($session_id, $server_id, $client_id, $email, $password, $quota);
						
						$row++;
					}
					fclose($handle);
					unlink($file);
				}
			}
			else {
				die("error in upload");
			}
		}
		
		//TODO put result in session flash message and redirect to emails urls (GET)
		$f3->reroute("/emails");
	}
	

	public static function domainsBulkGET (\Base $f3, array $url, string $controler)
	{
		$generation_start = microtime(true);
		
		$f3 = \Base::instance();
		
		$domains = IspConfig::IspGetDomains();
		$domains =  array_combine( array_column($domains, "id"), $domains ); // index by id
		$f3->set("domains", $domains);
		
		$domain_entries = IspConfig::IspGetDomainEntries();
		// convert zone id to domain name
		foreach ($domain_entries as &$domain_entry) {
			if(!empty($domain_entry["zone"]) && !empty($domains[$domain_entry["zone"]]))
				$domain_entry ["domain"] = $domains[ $domain_entry["zone"] ] [ "origin" ];
			else
				$domain_entry ["domain"] = "";
		}
		$stats ["total"] = count($domain_entries);
		
		// filter according to form text fields
		$domain_entries = array_filter(
			$domain_entries,
			function($domain_entry) {
				if(!empty($_GET["domain"])) {
					if (strpos($domain_entry["domain"], $_GET["domain"]) === FALSE) {
						return FALSE;
					}
				}
				if(!empty($_GET["type"])) {
					if (strpos($domain_entry["type"], $_GET["type"]) === FALSE) {
						return FALSE;
					}
				}
				if(!empty($_GET["name"])) {
					if (strpos($domain_entry["name"], $_GET["name"]) === FALSE) {
						return FALSE;
					}
				}
				if(!empty($_GET["data"])) {
					if (strpos($domain_entry["data"], $_GET["data"]) === FALSE) {
						return FALSE;
					}
				}
				return TRUE;
			}
		);
		$stats ["nb"] = count($domain_entries);
		$f3->set("stats", $stats);
		
		// order by domain; type, name
		array_multisort(
			array_column($domain_entries, 'domain'), SORT_ASC,
			array_column($domain_entries, 'type'), SORT_ASC,
			array_column($domain_entries, 'name'), SORT_ASC,
			$domain_entries
		);
		$f3->set("domain_entries", $domain_entries);
		
		$generation_end = microtime(true);
		$generation_time = number_format ( (($generation_end - $generation_start) * 1000 ), 0 , "," , " " ); // µs -> ms
		$footer_additional_text = ' | 
				generated in '.$generation_time .' ms';
		$f3->set("footer_additional_text", $footer_additional_text);
		
		$PAGE = [
			"name" => "domains/bulk",
			"title" => "DNS",
		];
		$f3->set("PAGE", $PAGE);

		$view = new \View();
		echo $view->render('domains/bulk.phtml');
	}
	
	
	public static function domainsBulkPOST () {
		$f3 = \Base::instance();
		
		$post = $f3->get("POST");
		$action = $post ["action"];
		
		if($action === "edit") {
			$data = $post ["data"];
			$name = $post ["name"];
	
			foreach ($post["domain_entry"] as $domain_entry_id => $on) {
				$result = IspConfig::IspSetDomainParams ($domain_entry_id, $name, $data);
			}
			
			//TODO check same type and data / name
			//TODO check type = A
		}
		else {
			throw new \Exception("unsupported action : " . $action);
		}
		
		$f3->reroute($f3->get('SERVER.HTTP_REFERER'));
	}
	
	
	public static function faviconGET (\Base $f3, array $url, string $controler)
	{
		$web = \Web::instance();
		$filename = __DIR__ . "/../../assets/app_icon.svg";
		$sent = $web->send($filename);
		if ($sent === false) {
			throw new ErrorException("web send error");
		}
	}
}
