<?php
abstract class JsonRpcConnection
{
	private $_phpws_client_id;
	private $_phpws_server;
	private $_rx_buf;
	private $_rxst;
	protected $_uid;
	public function __construct()
	{
		$this->_uid=md5(uniqid(rand()));
		$this->_rx_buf="";
		$this->_rxst=0;
	}
	public function _setPHPWebserverInfo($i_client_id,$i_server){
		$this->_phpws_client_id=$i_client_id;
		$this->_phpws_server=$i_server;
	}
	public function _pushRx($i_rx)
	{
		$len=strlen($i_rx);
		//ストリームからJSONを抽出。"のエスケープには対応しない。
		for($i=0;$i<$len;$i++){
			$t=$i_rx[$i];
			switch($this->_rxst){
				case 2:
					if($t=='"'){
						$this->_rxst=1;
					}
					break;
				case 0:
					if($t!='{'){
						continue 2;
					}
					$this->_rx_buf='{';
					$this->_rxst=1;
					continue 2;
				case 1:
					switch($t){
					case '"':
						$this->_rxst=2;
						break;
					case '}':
						$this->_rx_buf.='}';
						$this->_rxst=0;
						{
							$j= IgsJson::createInstance($this->_rx_buf);
							if($j==null){
								//JsonRPCでなければ切断
								$this->_phpws_server->wsClose($this->_phpws_client_id);
								return;
							}
							$this->onJson($j);
							if($this->_phpws_server->wsClients[$this->_phpws_client_id][2]!=1){
								//open以外ならループ終了
								return;
							}
						}
						continue 2;
					}
			}
			$this->_rx_buf.=$t;
		}
		return;
	}	
	public function isEqualClientId($i_phpws_client_id){
		return $i_phpws_client_id==$this->_phpws_client_id;
	}
	public function isEqualUid($i_uid){
		return $i_uid==$this->_uid;
	}
		
	public abstract function onOpen();
	//override
	public abstract function onClose();
	//override
	public abstract function onJson($i_json);
	/**
	 * closeはonCloseを発生させます。
	 */
	public function close()
	{
		$this->log(__METHOD__.':close by server.');
		$this->_phpws_server->wsClose($this->_phpws_client_id);
	}
	public function wsSendError($m)
	{
		$this->_phpws_server->wsSend(
				$this->_phpws_client_id,
				'{"jsonrpc": "2.0", "error":["'.$m.'"],"id":null}');
	}
	public function wsSend($m)
	{
		$this->_phpws_server->wsSend($this->_phpws_client_id,$m);
	}
	public function log($m)
	{
		$this->_phpws_server->log('['.$this->_phpws_client_id.']'.$m);
	}	
}
?>