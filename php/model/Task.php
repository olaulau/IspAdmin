<?php

namespace model;

abstract class Task {
	
	public abstract static function getCmd ($domain) ;
	
	public abstract static function extractInfos ($domain, $server) ;
	
}
