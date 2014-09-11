<?php
class JsonRpcProxyCtrlpoint extends JsonRpcProxyConnection
{
	public function __construct($i_ep)
	{
		parent::__construct();
		//Peerの設定
		$this->_peer=$i_ep;
		$i_ep->_peer=$this;
	}
	public function close()
	{
		$this->onClose();
		parent::close();
	}
	public function onOpen()
	{
		$this->log(__METHOD__);
		$this->_peer->wsSend('{"jsonrpc": "2.0", "method":"miigs:endpt:hello","params":[],"id":-1}');
	}
	public function onJson($i_json)
	{
		if($i_json->isMethod()){
			//制御コマンド
			switch($i_json->getMethod()){
				default:
					if(strpos($i_json->getMethod(),'miigs:')===0){
						$this->log(__METHOD__.':Invalid method.');
						//miigsNSの他のメソッドならエラー
						$this->wsSendError('Invalid method.');
						$this->close();
						return;
					}
					break;
			}
			//Method以外はパススルー
		}else if($i_json->isResult()){
		}
		$this->log($i_json->src);
		//そのままpeerへ送信
		$this->_peer->wsSend($i_json->src);
	}
	public function onClose()
	{
		$this->log(__METHOD__);
		if(isset($this->_peer)){
			$ep=$this->_peer;
			unset($ep->_peer);
			unset($this->_peer);
			$ep->wsSend('{"jsonrpc": "2.0", "method":"miigs:endpt:byebye","params":[],"id":-1}');
		}
	}
}

?>