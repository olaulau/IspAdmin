<?php
namespace service;

use ErrorException;


class Chronos
{
	
	private array $stack;
	
	
	public function start (string $name) : void
	{
		$chrono = new Chrono($name);
		$this->stack [] = $chrono;
	}
	
	public function stop () : void
	{
		$chrono = end($this->stack);
		if($chrono === false) {
			throw new ErrorException("no chrono started yet");
		}
		$chrono->stop();
	}
	
	
	public function __toString () : string
	{
		$res = "";
		foreach ($this->stack as $chrono) {
			$res .= ($chrono . PHP_EOL);
		}
		return $res;
	}
	
	
	public function getDuration () : float
	{
		$res = 0;
		foreach ($this->stack as $chrono) {
			$res += $chrono->getDuration();
		}
		return $res;
	}
	
	public function getDurationFormatted () : string
	{
		return number_format ( $this->getDuration(), 0 , "," , " " );
	}
	
}
