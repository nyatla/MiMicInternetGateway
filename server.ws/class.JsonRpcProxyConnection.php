<?php
abstract class JsonRpcProxyConnection extends JsonRpcConnection
{
	protected $_peer;
	public function hasPeer()
	{
		return isset($this->_peer);
	}
}
?>