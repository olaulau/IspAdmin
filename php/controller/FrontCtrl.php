<?php
namespace controller;

use DateTimeImmutable;
use ErrorException;
use model\DnsInfos;
use service\Chronos;
use service\IspcDomain;
use service\IspcMail;
use service\IspConfig;
use service\IspcWebsite;


class FrontCtrl extends Ctrl
{
	
	public static function beforeroute(\Base $f3, array $url, string $controler) : void
	{
		parent::beforeroute($f3, $url, $controler);
		
		if(empty( $f3->get("SESSION.auth_user") )) {
			$f3->reroute("/login");
		}
	}
	
	
	public static function afterroute(\Base $f3, array $url, string $controler) : void
	{
		
	}
	
	
	public static function homeGET (\Base $f3, array $url, string $controler) : void
	{
		$PAGE = [
			"name" => "index",
			"title" => "Isp Admin",
		];
		$f3->set("PAGE", $PAGE);
		
		$view = new \View();
		echo $view->render('index.phtml');
	}
	
	public static function testGET (\Base $f3, array $url, string $controler) : void
	{
		$PAGE = [
			"name" => "test",
			"title" => "test",
		];
		$f3->set("PAGE", $PAGE);
		
		$view = new \View();
		echo $view->render('test.phtml');
	}
	
	
	public static function websitesListGET (\Base $f3, array $url, string $controler) : void
	{
		$generation_start = microtime(true);
		
		// get servers and websites list
		$cache = \Cache::instance();
		$key = "servers_configs";
		if ($cache->exists($key, $servers_configs) === false) { //TODO count in stats
			$servers_configs = IspcWebsite::getServersConfigs ();
			$cache->set($key, $servers_configs, $f3->get("cache.ispconfig"));
		}
		$f3->set("servers_configs", $servers_configs);
		
		$vhosts = IspcWebsite::getAll ("vhost");
		$f3->set("vhosts", $vhosts);
		
		// filter domains (dev tests)
		if(!empty($f3->get('debug.websites_filter'))) {
			array_walk ( $vhosts , function ( $value , $key , $filter ) use ( &$vhosts ) {
				if (strpos($value ["domain"], $filter) === false) {
					unset ($vhosts [$key]); // dev test by filtering domain
				}
			} , $f3->get('debug.websites_filter') );
		}
		if(!empty($f3->get('debug.websites_max_number'))) {
			$vhosts = array_slice($vhosts, 0, $f3->get('debug.websites_max_number'), true); // dev test with few domains
		}
		
		// sort domains alphabetically
		array_multisort(array_column($vhosts, "domain"), SORT_ASC, $vhosts);
		$f3->set("vhosts", $vhosts);
		
		$generation_end = microtime(true);
		$generation_time = number_format ( (($generation_end - $generation_start) * 1000 ), 0 , "," , " " ); // µs -> ms
		$footer_additional_text = ' | 
			generated in ' . $generation_time .' ms';
		$f3->set("footer_additional_text", $footer_additional_text);
		
		$PAGE = [
			"name" => "websites/list",
			"title" => "Web Sites",
		];
		$f3->set("PAGE", $PAGE);
		
		$view = new \View();
		echo $view->render('websites/list.phtml');
	}
	
	
	public static function websiteDetailGET (\Base $f3, array $url, string $controler) : void
	{
		$chronos = new Chronos ();
		
		// get vhost
		$vhost_id = intval($f3->get("PARAMS.id"));
		if(empty($vhost_id) || !is_integer($vhost_id)) {
			throw new ErrorException("invalid parameter");
		}
		$chronos->start("sites_web_domain_get");
		$vhost = IspcWebsite::get($vhost_id);
		$chronos->stop();
		$f3->set("vhost", $vhost);
		$server_id = $vhost ["server_id"];
		
		$ssl = "no";
		if($vhost ["ssl"] === "y") {
			$ssl = "yes";
			if($vhost ["ssl_letsencrypt"] === "y") {
				$ssl = "Let's encrypt";
				if($vhost ["rewrite_to_https"] === "y") {
					$ssl .= " with redirection";
				}
			}
		}
		$f3->set("ssl", $ssl);
		
		// get php infos
		$chronos->start("server_get_php_versions");
		$server_phps = IspcWebsite::getServerPhps($server_id);
		$chronos->stop();
		if(!empty($vhost ["server_php_id"])) {
			$php_name = $server_phps [$vhost ["server_php_id"]] ["name"];
		}
		else {
			$php_name = "";
		}
		$f3->set("php_name", $php_name);
		
		// get shell users
		$chronos->start("sites_shell_user_get");
		$shell_users = IspcWebsite::getShellUser($vhost ["domain_id"]);
		$chronos->stop();
		$f3->set("shell_users", $shell_users);
		
		// backups
		$chronos->start("sites_web_domain_backup_list");
		$backups = IspcWebsite::getBackups($vhost_id);
		$chronos->stop();
		$backups_by_date = [];
		$backups = IspcWebsite::getBackups($vhost_id);
		$f3->set("backups", $backups);
		
		foreach ($backups as $backup) {
			$datetime = DateTimeImmutable::createFromFormat("U", $backup ["tstamp"]);
			$backups_by_date [$datetime->format("Y-m-d")] [] = $backup;
		}
		$f3->set("backups_by_date", $backups_by_date);
		
		$footer_additional_text = ' | 
			<span title="' . $chronos . '">generated in ' . $chronos->getDurationFormatted() .' ms</span>
			';
		$f3->set("footer_additional_text", $footer_additional_text);
		
		$PAGE = [
			"name" => "websites/detail",
			"title" => "Website : {$vhost ["domain"]}",
		];
		$f3->set("PAGE", $PAGE);
		
		$view = new \View();
		echo $view->render('websites/detail.phtml');
	}
	
	
	public static function websitesCheckGET (\Base $f3, array $url, string $controler) : void
	{
		$generation_start = microtime(true);
		
		// get servers and websites list
		$cache = \Cache::instance();
		$key = "servers_configs";
		if ($cache->exists($key, $servers_configs) === false) { //TODO count in stats
			$servers_configs = IspcWebsite::getServersConfigs ();
			$cache->set($key, $servers_configs, $f3->get("cache.ispconfig"));
		}
		$key = "websites";
		if ($cache->exists($key, $websites_all) === false) { //TODO count in stats
			$websites_all = IspcWebsite::getAll ();
			$cache->set($key, $websites_all, $f3->get("cache.ispconfig"));
		}
		$key = "servers_phps";
		if ($cache->exists($key, $servers_phps) === false) { //TODO count in stats
			$servers_phps = IspcWebsite::getServersPhps ();
			$cache->set($key, $servers_phps, $f3->get("cache.ispconfig"));
		}
		
		// filter domains (dev tests)
		if(!empty($f3->get('debug.websites_filter'))) {
			array_walk ( $websites_all , function ( $value , $key , $filter ) use ( &$websites_all ) {
				if (strpos($value ["domain"], $filter) === false) {
					unset ($websites_all [$key]); // dev test by filtering domain
				}
			} , $f3->get('debug.websites_filter') );
		}
		if(!empty($f3->get('debug.websites_max_number'))) {
			$websites_all = array_slice($websites_all, 0, $f3->get('debug.websites_max_number'), true); // dev test with few domains
		}
		
		// add 2LD info
		foreach ($websites_all as $website_id => $website) {
			$domain = $website ['domain'];
			$two_ld = DnsInfos::getParent($domain);
			$websites_all [$website_id] ['2LD'] = $two_ld;
		}
		
		// get websites infos (by running external processes)
		$cmds = [];
		$tasks = [];
		foreach ($websites_all as $website_id => $website) {
			$two_ld = $website ["2LD"];
			$domain = $website ["domain"];
			$server_id = $website ["server_id"];
			if ($website ['active'] === 'y') {
				if(!empty($servers_configs [$server_id])) {
					$server = $servers_configs [$server_id];
					if ($f3->get('active_modules.whois') === true) { // do not recreate things if parents domain has already been done
						if(empty($tasks ["whois"] [$two_ld])) {
							$t = new \model\WhoisInfos ($two_ld, $server);
							$tasks ["whois"] [$two_ld] = $t;
							$cmds ["whois_$two_ld"] = $t->getCmd();
						}
					}
					if ($f3->get('active_modules.dns') === true) {
						$t = new \model\DnsInfos ($domain, $server);
						$tasks ["dns"] [$domain] = $t;
						$cmds ["dns_$domain"] = $t->getCmd();
					}
					if ($f3->get('active_modules.ssl') === true) {
						$t = new \model\SslInfos ($domain, $server);
						$tasks ["ssl"] [$domain] = $t;
						$cmds ["ssl_$domain"] = $t->getCmd();
					}
					if ($f3->get('active_modules.http') === true) {
						$t = new \model\HttpInfos ($domain, $server);
						$tasks ["http"] [$domain] = $t;
						$cmds ["http_$domain"] = $t->getCmd();
					}
					if ($f3->get('active_modules.php') === true) {
						$t = new \model\PhpInfos ($domain, $server, $website, $servers_phps [$server_id]);
						$tasks ["php"] [$domain] = $t;
					}
				}
			}
		}
		
		// stats & execution
		$stats = [];
		$stats ['total_cmds'] = count($cmds);
		execMultipleProcesses($cmds, true, true);
		$stats ['total_executed_cmds'] = count($cmds);
		$stats ['executed_cmds'] = $cmds;
		
		// extract infos
		foreach ($websites_all as $website_id => &$website) {
			$two_ld = $website ["2LD"];
			$domain = $website ["domain"];
			if ($website ['active'] === 'y') {
				if(!empty($servers_configs [$website ["server_id"]])) {
					$server = $servers_configs [$website ["server_id"]]; //TODO useless
					if ($f3->get('active_modules.whois') === true) {
						$tasks ["whois"] [$two_ld]->extractInfos ($website);
						$website ['whoisInfos'] = $tasks ["whois"] [$two_ld];
					}
					
					if ($f3->get('active_modules.dns') === true) {
						$tasks ["dns"] [$domain]->extractInfos ($website);
						$website ['dnsInfos'] = $tasks ["dns"] [$domain];
					}
					
					if ($f3->get('active_modules.ssl') === true) {
						$tasks ["ssl"] [$domain]->extractInfos ($website);
						$website ['sslInfos'] = $tasks ["ssl"] [$domain];
					}
					if ($f3->get('active_modules.http') === true) {
						$tasks ["http"] [$domain]->extractInfos ($website);
						$website ['httpInfos'] = $tasks ["http"] [$domain];
					}
					if ($f3->get('active_modules.php') === true) {
						$tasks ["php"] [$domain]->extractInfos ($website);
						$website ['phpInfos'] = $tasks ["php"] [$domain];
					}
				}
			}
			unset($website);
		}
		
		// separate websites by types
		$websites_by_type = group2dArray($websites_all, "type");
		$websites_grouped = $websites_by_type ["vhost"] ?? [];
		$aliases = $websites_by_type ["alias"] ?? [];
		$subdomains = $websites_by_type ["subdomain"] ?? [];
		
		// group vhosts by 2LD & make stats
		$stats ["vhost_count"] = count($websites_grouped);
		$websites_grouped = group2dArray($websites_grouped, "2LD");
		$stats ["2LD_count"] = count($websites_grouped);
		
		// group aliases & subdomains by 'parent_domain_id' & stats
		$stats ["aliases_count"] = count($aliases);
		$aliases = group2dArray($aliases, "parent_domain_id");
		$stats ["subdomains_count"] = count($subdomains);
		$subdomains = group2dArray($subdomains, "parent_domain_id");
		
		$f3->set('stats', $stats);
		$f3->set('websites_grouped', $websites_grouped);
		$f3->set('aliases', $aliases);
		$f3->set('subdomains', $subdomains);
		
		$generation_end = microtime(true);
		$generation_time = number_format ( (($generation_end - $generation_start) * 1000 ), 0 , "," , " " ); // µs -> ms
		$footer_additional_text = ' | 
			<span title="' . implode(PHP_EOL, $stats ['executed_cmds']).'">' . $stats ['total_executed_cmds'] . ' / ' . $stats ['total_cmds'] . ' executed</span>
				| 
			generated in ' . $generation_time .' ms';
		$f3->set("footer_additional_text", $footer_additional_text);
		
		$PAGE = [
			"name" => "websites/check",
			"title" => "Web Sites",
		];
		$f3->set("PAGE", $PAGE);
		
		$view = new \View();
		echo $view->render('websites/check.phtml');
	}
	
	
	public static function emailsBulkGET (\Base $f3, array $url, string $controler) : void
	{
		$PAGE = [
			"name" => "emails/bulk",
			"title" => "E-mails",
		];
		$f3->set("PAGE", $PAGE);
		
		$view = new \View();
		echo $view->render('emails/bulk.phtml');
	}
	
	
	public static function emailsBulkPOST (\Base $f3, array $url, string $controler) : void
	{
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
		
		$mail_domains = [];
		// uploaded file loop
		foreach ($files as $file => $uploaded) {
			if($uploaded === true) {
				// row loop
				if (($handle = fopen($file, "r")) !== FALSE) {
					while (($data = fgetcsv($handle)) !== FALSE) {
						list($email, $password) = $data;
						
						// delete mail user
						$mail_user = IspcMail::IspGetMailUser($email);
						if(!empty($mail_user)) { // existing email
							if($drop_existing_mailboxes === true) {
								IspcMail::IspDeleteMailUser($mail_user["mailuser_id"]);
							}
							else {
								continue; // can't recreate an existing mailbox
							}
						}
						
						// calculate domain
						if(filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
							throw new ErrorException("invalid email : $email");
						}
						list($email_username, $email_domain) = explode("@", $email);

						// look for server and client hosting mail domain (if not in cache)
						if(empty($mail_domains [$email_domain])) {
							$mail_domain = IspcMail::IspGetMailDomain($email_domain);
							if(empty($mail_domain)) {
								throw new ErrorException("mail domain does not exist");
							}
							$client_id = IspConfig::IspGetClientIdFromUserId($mail_domain ["sys_groupid"]);
							$mail_domain ["client_id"] = $client_id;
							$mail_domains [$email_domain] = $mail_domain;
						}
						
						// create mailbox
						$server_id = $mail_domains [$email_domain] ["server_id"];
						$client_id = $mail_domains [$email_domain] ["client_id"];
						$quota = $f3->get("POST.quota") * 1024 * 1024 * 1024; // GB -> Bytes
						$mail_user_id = IspcMail::IspAddMailUser($server_id, $client_id, $email, $password, $quota);
					}
					fclose($handle);
					unlink($file);
				}
			}
			else {
				throw new ErrorException("error in upload");
			}
		}
		
		//TODO put result in session flash message and redirect to emails urls (GET)
		$f3->reroute();
	}
	

	public static function domainsBulkGET (\Base $f3, array $url, string $controler) : void
	{
		$generation_start = microtime(true);
		
		$domains = IspcDomain::IspGetDomains();
		$domains =  array_combine( array_column($domains, "id"), $domains ); // index by id
		$f3->set("domains", $domains);
		
		$domain_entries = IspcDomain::IspGetDomainEntries();
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
	
	
	public static function domainsBulkPOST (\Base $f3, array $url, string $controler) : void
	{
		$post = $f3->get("POST");
		$action = $post ["action"];
		
		if($action === "edit") {
			$data = $post ["data"];
			$name = $post ["name"];
	
			foreach ($post["domain_entry"] as $domain_entry_id => $on) {
				$result = IspcDomain::IspSetDomainParams ($domain_entry_id, $name, $data);
			}
			
			//TODO check same type and data / name
			//TODO check type = A
		}
		else {
			throw new \Exception("unsupported action : " . $action);
		}
		
		$f3->reroute($f3->get('SERVER.HTTP_REFERER'));
	}
	
	
	public static function faviconGET (\Base $f3, array $url, string $controler) : void
	{
		$web = \Web::instance();
		$filename = __DIR__ . "/../../assets/app_icon.svg";
		$sent = $web->send($filename);
		if ($sent === false) {
			throw new ErrorException("web send error");
		}
	}
}
