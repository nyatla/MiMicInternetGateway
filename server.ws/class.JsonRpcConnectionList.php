<?php
class JsonRpcConnectionList
{
	private $_ws_list=array();

	/**object指定で追加*/
	public function add($i_item)
	{
		$this->_ws_list[]=$i_item;
	}
	/**object指定でリストから削除*/
	public function removeByIdx($i_idx)
	{
		$r=$this->_ws_list[$i_idx];
		unset($this->_ws_list[$i_idx]);
		array_splice($this->_ws_list,$i_idx,1);
		return $r;
	}
	public function removeByClientId($i_client_id)
	{
		$i=$this->getItemIdxByClientId($i_client_id);
		if($i<0){
			return null;
		}
		return $this->removeByIdx($i);
	}

	
	public function getItemIdxByClientId($i_client_id)
	{
		for($i=0;$i<count($this->_ws_list);$i++){
			if($this->_ws_list[$i]->isEqualClientId($i_client_id)){
				return $i;
			}
		}
		return -1;
	}
	public function getItemByClientId($i_client_id)
	{
		$i=$this->getItemIdxByClientId($i_client_id);
		if($i<0){
			return null;
		}
		return $this->_ws_list[$i];
	}
	public function getItemIdxByUid($i_uid)
	{
		for($i=0;$i<count($this->_ws_list);$i++){
			if($this->_ws_list[$i]->isEqualUid($i_uid)){
				return $i;
			}
		}
		return -1;
	}

	public function getItemByUid($i_uid)
	{
		$i=$this->getItemIdxByUid($i_uid);
		if($i<0){
			return null;
		}
		return $this->_ws_list[$i];
	}
}


?>