<?php
class Logger
{
	private $fp;
	public function log($v)
	{
		flock($this->fp, LOCK_EX);
		fwrite($this->fp, $v."\r\n");
		fflush($this->fp);
		flock($this->fp, LOCK_UN);
	}
	public function __construct(){
		$this->fp = fopen("log.txt", "a");
	}
	public function __destruct (){
		fclose($this->fp);
	}
}
?>