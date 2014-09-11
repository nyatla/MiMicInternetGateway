<?php
class IgsJson
{
	public $src;
	public $json;
	public static function createInstance($i_src)
	{
		$inst=new IgsJson();
		$inst->src=$i_src;
		$inst->json=$j=json_decode($i_src);
		if($j==null){
			return null;
		}
//		めそっどしかない
		if(isset($inst->json->{'method'}))
		{
			//methodの時はid/paramsがあること。paramsがarrayであること。
			if(	property_exists($inst->json,'id')&&
				property_exists($inst->json,'params') &&
				is_array($inst->json->{'params'}))
			{
				//methodの場合、
				return $inst;
			}
		}
		if(property_exists($inst->json,'result'))
		{
			if(	property_exists($inst->json,'id')&&
				property_exists($inst->json,'result') &&
				is_array($inst->json->{'result'}))
			{
				//methodの場合、
				return $inst;
			}
		}
		return null;
	}
	private function __construct(){}
	public function isMethod()
	{
		return property_exists($this->json,'method');
	}
	public function isResult()
	{
		return property_exists($this->json,'result');
	}	
	public function getMethod(){
		$p=$this->json->{'method'};
		return $p;
	}
	public function getId(){
		$p=$this->json->{'id'};
		return $p;
	}
	
	public function getMethodParam($idx){
		$p=$this->json->{'params'};
		if(count($p)<=$idx){
			return null;
		}
		return $p[$idx];
	}
}
?>