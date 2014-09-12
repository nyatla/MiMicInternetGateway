<?php
class JsonRpcProxyEndpoint extends JsonRpcProxyConnection
{
	public function close()
	{
		$this->onClose();
		//自分自身をクローズ
		parent::close();
	}
	public function onOpen()
	{
		$this->log(__METHOD__);
		$this->wsSend('{"jsonrpc": "2.0", "method":"miigs:endpt:ready","params":["'.$this->_uid.'"],"id":-1}');
	}
	public function onJson($i_json)
	{
		$this->log(__METHOD__.':'.$i_json->src);
		if($i_json->isResult()){
			//Epから送られてくるresultは全部スルーパス
			if(isset($this->_peer)){
				//peer切断後にonJsonとかあるみたい
				$this->_peer->wsSend($i_json->src);
			}
			return;
		}
		if($i_json->isMethod()){
			//制御コマンド
			switch($i_json->getMethod()){
			case 'miigs:endpt:ondevclose'://params:[]
			case 'miigs:endpt:ondeverror'://params:[]
				//コントロールポイントの切断
				if(isset($this->_peer)){
					//存在すればpeerの切断
					$this->_peer->close();
					$this->_peer=null;
				}
				return;
			default:
			}
			$this->log(__METHOD__.':Invalid method.');
			//miigsNSの他のメソッドならエラー
			$this->wsSendError('Invalid method.');
			$this->close();
		}
		return;
	}
	public function onClose()
	{
		$this->log(__METHOD__);
		if(isset($this->_peer)){
			//Peer登録の解除
			$cp=$this->_peer;
			unset($this->_peer);
			unset($this->_peer->_peer);
			//PeerのCpクローズ
			$cp->close();
		}
	}
}
?>