<?php

class ControlPoint
{
	public $fp;
	private $tx;
	public function update($i_tx)
	{
		flock($this->fp, LOCK_EX);
		$line0=fgets($this->fp);
		$line1=fgets($this->fp);
		$line2=fgets($this->fp).$i_tx;
		rewind($this->fp);
		ftruncate($this->fp,0);
		fputs($this->fp,$line0);
		fputs($this->fp,'');
		fputs($this->fp,$line2);
		flock($this->fp, LOCK_UN);
		if((time()-$line0>30)){
			//30sec更新がない時エラーね。
			throw new Exception();
		}		
		return $line1;
	}	
	public function __construct($i_sid){
		$this->fp = @fopen($i_sid.".fifo.txt", "r+");
		if(!$this->fp){
			throw new Exception();
		}
		//Clientの時
		flock($this->fp, LOCK_EX);
		$line0=fgets($this->fp);
		flock($this->fp, LOCK_UN);
		if(($line0===FALSE)||(time()-$line0>30)){
			//30sec更新がない時/ファイルが空ならエラーね。
			throw new Exception();
		}
	}
	public function __destruct ()
	{
		fclose($this->fp);
	}	
}
?>