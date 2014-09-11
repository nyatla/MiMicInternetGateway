<?php
// prevent the server from timing out
set_time_limit(0);
date_default_timezone_set("Asia/Tokyo");

/**
 * miigs
 * 	endpt
 * 		new			[ep>sv] - 新しいendpointの生成要求
 * 		ondevclose	[ep>sv] - デバイスの切断通知
 * 		ondeverror	[ep>sv] - デバイスのエラー切断通知
 * 		opendev		[sv>ep] - デバイスのオープン要求(WS open)
 * 		closedev	[sv>ep] - デバイスのクローズ要求(WS close)
 * 	ctrlpt
 * 		new[cp>sv] - 新しいctrlpointの生成
 */

/**
 * 接続手順
 * endpoint
 * {"method":"miigs:endpoint:new"}
 * ctrlpoint
 * {"method":"miigs:ctrlpoint:new"}
 */

// include the web sockets server script (the server is started at the far bottom of this file)
require 'class.IgsJson.php';
require 'class.PHPWebSocket.php';

require 'class.JsonRpcConnection.php';
require 'class.JsonRpcConnectionList.php';
require 'class.JsonRpcServer.php';
require 'class.JsonRpcProxyConnection.php';
require 'class.JsonRpcProxyEndpoint.php';
require 'class.JsonRpcProxyCtrlpoint.php';

class JsonRpcProxy extends JsonRpcServer
{
	public function __construct()
	{
		parent::__construct();
		$this->log("Hello, Start".__METHOD__);
	}
	protected function OnNewConnection($i_wsphp_client)
	{
		$req=parse_url($i_wsphp_client[100]);
		if($req===FALSE || !array_key_exists('path',$req)){
			return null;
		}
		switch($req['path']){
		case '/endpt':
			$this->log(__METHOD__.':endpoint');
			return new JsonRpcProxyEndpoint();
		case '/ctrlpt':
			if(array_key_exists('query',$req)){
				parse_str($req['query'],$q);
				if(array_key_exists('ep',$q)){
					$ep=$this->getConnectionByUid($q['ep']);
					if(isset($ep)){
						if(!$ep->hasPeer()){
							$this->log(__METHOD__.':ctrlpoint:'.$q['ep']);
							return new JsonRpcProxyCtrlpoint($ep);
						}
					}
				}
			}
		}
		return null;
	}
}

$Server = new JsonRpcProxy();
$Server->wsStartServer('127.0.0.1', 9300);

?>