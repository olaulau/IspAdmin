<?php
namespace service;


class Chrono
{
	
	private float $start, $end, $duration;
	
	
	function __construct (public string $name)
	{
		$this->start = microtime(true);
	}
	
	
	public function stop () : void
	{
		$this->end = microtime(true);
		$this->duration = $this->end - $this->start; // µs
	}
	
	
	public function getName () : string
	{
		return $this->name;
	}
	
	public function getDuration () : float
	{
		return $this->duration * 1000; // ms
	}
	
	public function getDurationFormatted () : string
	{
		return number_format ( $this->getDuration(), 0 , "," , " " );
	}
	
	
	public function __toString () : string
	{
		return "{$this->getDurationFormatted()} {$this->getName()}";
	}
	
}
