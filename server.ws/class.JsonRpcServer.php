<?php 
/**
 * JsonRPCサーバのベースクラス。
 * OnNewConnectionを定義します。
 */
abstract class JsonRpcServer extends PHPWebSocket
{
	private $_connection_list;
	public function __construct()
	{
		$this->_connection_list=new JsonRpcConnectionList();
	}

	abstract protected function OnNewConnection($i_wsphp_client);

	protected function OnMessage($clientID, $message, $messageLength, $binary)
	{
		$item=$this->_connection_list->getItemByClientId($clientID);
		if ($messageLength == 0 || $binary){
			return;
		}
		//pushRxはonJsonを発生させるけどcloseした場合は途中で受信をやめる。
		$item->_pushRx($message);
	}
	//Openハンドラ
	protected function OnOpen($clientID)
	{
		$this->log("[$clientID]".__METHOD__);
		//ハンドラコール
		$ret=$this->onNewConnection($this->wsClients[$clientID]);
		if(isset($ret)){
			$ret->_setPHPWebserverInfo($clientID,$this);
			$this->_connection_list->add($ret);
			$ret->onOpen();
			return;
		}
		$this->wsClose($clientID);
		return;
	}
	//Closeハンドラ
	protected function OnClose($clientID, $status)
	{
		$this->log("[$clientID]JsonRpcServer::OnClose");
		//log
		$item=$this->_connection_list->getItemByClientId($clientID);
		if(isset($item)){
			$item->onClose();
			$this->_connection_list->removeByClientId($clientID);
		}
	}
	public function getConnectionByUid($i_uid)
	{
		return $this->_connection_list->getItemByUid($i_uid);
	}
}


?>