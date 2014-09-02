<?php
//require_once("Logger.php");
require_once("Io.php");
//$logger=new Logger();
function logging($v)
{
	global $logger;
//	$logger->log($v);
}
const ST_IDLE=1;
const DATA_PATH="data/";

class SockFile
{
	private $_fp;
	public $e_time;
	public $e_buf;
	public $c_time;
	public $c_buf;
	public $e_key;
	public $c_key;
	public function __construct($fp)
	{
		$this->_fp=$fp;		
		$this->e_time=0;
		$this->c_time=0;
		$this->e_buf="";
		$this->c_buf="";
		$this->e_key="";
		$this->c_eky="";
		
	}
	public function read(){
		rewind($this->_fp);
		$this->e_time=trim(fgets($this->_fp));
		$this->c_time=trim(fgets($this->_fp));
		$this->e_buf=trim(fgets($this->_fp));
		$this->c_buf=trim(fgets($this->_fp));
		$this->e_key=trim(fgets($this->_fp));
		$this->c_key=trim(fgets($this->_fp));
	}
	public function write(){
		rewind($this->_fp);
		ftruncate($this->_fp,0);
		fputs(
			$this->_fp,
			$this->e_time."\r\n".
			$this->c_time."\r\n".
			$this->e_buf."\r\n".
			$this->c_buf."\r\n".
			$this->e_key."\r\n".
			$this->c_key."\r\n");
	}
	/**
	 * Endpointペイロードを追加してControlpointペイロードを返してリセットする。
	 * @param unknown_type $v
	 * @return string
	 */
	public function updateEp($v,$time){
		if(strlen($v)>0){
			$this->e_buf.=$v;//E->Cパケット
		}
		$r=$this->c_buf;
		$this->c_buf="";
		$this->e_time=$time;
		
		return $r;
	}
	public function updateCp($v,$time){
		if(strlen($v)>0){
			$this->c_buf.=$v;//C->Eパケット
		}
		$r=$this->e_buf;
		$this->e_buf="";
		$this->c_time=$time;
		return $r;
	}	
	public static function filename($i_sid)
	{
		return DATA_PATH.$i_sid.'.fifo.txt';
	}
	public static function remove($i_sid)
	{
		$fn=DATA_PATH.$i_sid.'.fifo.txt';
		if(file_exists($fn)){
			logging("unlink!");
			unlink($fn);
			return true;
		}
		return false;
	}
}



function killSession($i_sid=FALSE)
{
	if($i_sid!==FALSE){
		SockFile::remove($i_sid);
	}
	header('HTTP', true, 500);
	echo "error";
}
//セッション情報の記録

/**
 * Endpoint側の関数
 * ?cmd=listen
 * {sid:[SID:string]}
 */
function e_listen()
{
	logging("<e_listen>");
	//既存のsessionがある？
	if(isset($_REQUEST['sid'])){
		logging(__line__);
		//sidがセットされてたらそのエンドポイントを削除
		killSession($_REQUEST['sid']);
	}
	$sid="testid0000";
	//	$sid'=uniqid();
	//新規作成
	$fp = @fopen(SockFile::filename($sid),"a+");
	if($fp===FALSE){
		logging(__line__);
		killSession();
		return;
	}
	//新規作成の場合
	$now=time();
	
	flock($fp,LOCK_EX);
	$f=new SockFile($fp);
	$f->e_time=$now;
//	$f->e_key='E0000';
	$f->e_key='E'.uniqid();
	$f->write($fp);
	flock($fp,LOCK_UN);
	fclose($fp);	
	unset($fp);
	header('Content-Type: text/javascript; charset=utf-8');
	echo('{"sid":"'.$sid.'","key":"'.$f->e_key.'"}');
	logging("</e_listen>");
}

/**
 * EndPointのペイロード
 */
function e_sync()
{
	logging("<e_sync>");
	if(!isset($_REQUEST['sid'])){
		$log->log(__line__);
		killSession();
		return;
	}
	$sid=$_REQUEST['sid'];
	if(!isset($_REQUEST['key'])){
		logging(__line__);
		killSession($sid);
		return;
	}
	$ekey=$_REQUEST['key'];
	$fp = @fopen(SockFile::filename($sid),"a+");
	if($fp===FALSE){
		logging(__line__);
		killSession($sid);
		return;
	}
	$now=time();
	//既存の接続
	flock($fp, LOCK_EX);
	$f=new SockFile($fp);
	$f->read($fp);
	
	if($f->e_key!=$ekey){
		//エンドポイントキーが不一致なら何もしない。
		flock($fp, LOCK_UN);
		fclose($fp);
		header('HTTP', true, 500);
		logging(__line__);
		return;
	}
	if($now-$f->c_time>30 && $f->c_key!=''){
		//CtrlPointのインターバルが30sec超えた or c_keyが有る。->コントロールポイントの情報クリア
		$f->c_buf="";
		$f->c_time=0;
		$f->c_key="";
		//エンドポイントの送信メモリクリア
		$f->e_buf="";
	}
	$rx=(isset($_REQUEST['payload'])?$_REQUEST['payload']:"");
	$tx=$f->updateEp($rx,$now);
	$f->write($fp);
	flock($fp, LOCK_UN);
	fclose($fp);
	if($now-$f->e_time>30){
		//Endpointのインターバルが30sec超えた(エラー)
		killSession();
		logging(__line__);
		return;
	}
	echo json_encode(array(
		"ckey"=>$f->c_key,
		"payload"=>$tx
	));
	logging("</e_sync>");
	return;	
}

function c_sync()
{
	logging("<c_sync>");
	if(!isset($_REQUEST['sid'])){
		logging(__line__);
		header('HTTP', true, 500);
		return;
	}
	$sid=$_REQUEST['sid'];
	if(!isset($_REQUEST['key'])){
		logging(__line__);
		header('HTTP', true, 500);
		return;
	}
	$ckey=$_REQUEST['key'];
	$fp = @fopen(SockFile::filename($sid),"a+");
	if($fp===FALSE){
		logging(__line__);
		killSession($sid);
		return;
	}
	$now=time();
	//既存の接続
	flock($fp, LOCK_EX);
	$f=new SockFile($fp);
	$f->read($fp);
	if($f->c_key!=$ckey){
		//エンドポイントキーが不一致なら何もしない。
		flock($fp, LOCK_UN);
		fclose($fp);
		header('HTTP', true, 500);
		logging(__line__);
		return;
	}
	if($f->c_time==0){
		//コントロールポイントがタイムアウト済ならエラーっすよ
		flock($fp, LOCK_UN);
		fclose($fp);
		header('HTTP', true, 500);
		logging(__line__);
		return;
	}
	if($now-$f->e_time>30 && $f->c_key!=''){
		//EndPointのインターバルが30sec超えたコントロールポイントをタイムアウトさせる
		$f->c_time=0;
		flock($fp, LOCK_UN);
		fclose($fp);
		header('HTTP', true, 500);
		logging(__line__);
	}
	$rx=(isset($_REQUEST['payload'])?$_REQUEST['payload']:"");
	$tx=$f->updateCp($rx,$now);
	$f->write($fp);
	flock($fp, LOCK_UN);
	fclose($fp);
	if($now-$f->e_time>30){
		//Endpointのインターバルが30sec超えた(エラー)
		killSession();
		logging(__line__);
		return;
	}
	echo json_encode(array(
			"payload"=>$tx
	));
	logging("</e_sync>");
	return;
}
/**
 * ?cmd=connect&sid=SOCKET_ID
 * {sid:[SID:string]}
 */
function c_connect()
{
	logging("<connect>");
	//セッション情報を新しく返す。
	$sid=$_REQUEST['sid'];
//	$key=$_REQUEST['key'];
	//新規作成
	$fp = @fopen(SockFile::filename($sid),"r+");
	if($fp===FALSE){
		logging(__line__);
		header('HTTP', true, 500);
		return;
	}
	$is_success=false;
	$now=time();
	flock($fp, LOCK_EX);
	$f=new SockFile($fp);
	$f->read($fp);
	
	if($f->c_key!=''){
		//キーがあいてなければエラー
	}else if($now-$f->e_time>30){
		//エンドポイントの更新が30秒なければエラー
	}else{
		$f->c_time=$now;		//コントロールポイントの更新時刻更新
		$f->c_key='C'.uniqid();	//コントロールポイントのID設定
		$f->c_buf="";			//送信バッファ初期化
		$f->write();
		$is_success=true;
	}
	flock($fp, LOCK_UN);
	fclose($fp);
	unset($fp);
	if($is_success){
		header('Content-Type: text/javascript; charset=utf-8');
		echo('{"sid":"'.$sid.'","key":"'.$f->c_key.'"}');
	}else{
		logging(__line__);
		header('HTTP', true, 500);
	}
	logging("</connect>");
	return;
}


function e_close()
{
	logging("<e_close>");
	if(isset($_REQUEST['sid'])){
		SockFile::remove($_REQUEST['sid']);
		logging(__line__);
	}else{
		header('HTTP', true, 500);
		logging(__line__);
	}
	logging("</e_close>");
}
function c_close()
{
	logging("<c_close>");
	if(!isset($_REQUEST['sid'])){
		header('HTTP', true, 500);
		logging(__line__);
	}else{
		$fp = @fopen(SockFile::filename($_REQUEST['sid']),"r+");
		if($fp===FALSE){
			logging(__line__);
			header('HTTP', true, 500);
			return;
		}
		//強制的にタイムアウトさせる
		flock($fp, LOCK_EX);
		$f=new SockFile($fp);
		$f->read($fp);
		$f->c_time=0;
		$f->write($fp);
		flock($fp, LOCK_UN);
		fclose($fp);
		unset($fp);
		logging(__line__);
	}
	logging("</c_close>");
}

date_default_timezone_set("Asia/Tokyo");
session_start();
switch($_REQUEST['cmd']){
case 'listen':
	e_listen();
	break;
case 'connect':
	c_connect();
	break;
case 'sync':
	if($_REQUEST['key'][0]=='E'){
		e_sync();
	}else{
		c_sync();
	}
	break;
case 'close':
	if($_REQUEST['key'][0]=='E'){
		e_close();
	}else{
		c_close();
	}
	break;
	
case 'open':
	break;
default:
	killSession();
	break;
}
?>