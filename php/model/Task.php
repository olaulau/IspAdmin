<?php

namespace model;

abstract class Task {
	
	protected $domain;
	protected $server;
	
	protected $labelType;
	protected $labelString;
	
	
	public function __construct ($domain, $server) {
		$this->domain = $domain;
		$this->server = $server;
	}
	
	public abstract function getCmd () ;
	
	public static function wrapCli () {
		$f3 = \Base::instance();
		$domain = $f3->get('PARAMS.domain');
		$t = new static($domain, null);
		$t->execCmd();
	}
	
	public abstract function execCmd () ;
	
	public abstract function extractInfos () ;
	
	
	public function getLabelType () {
		return $this->labelType;
	}
	
	public function getLabelString () {
		return $this->labelString;
	}
	
}
