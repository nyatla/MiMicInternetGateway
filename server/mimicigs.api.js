var MiMicIGS={};
MiMicIGS.version="0.1.0a";
(function()
{
	var MI=MiMicJS;
	MiMicIGS.Rpc=function(i_event){
		//関数の継承
		for(var k in MiMicJS.Rpc.prototype){
			if(!this[k]){
				this[k]=MiMicJS.Rpc.prototype[k];
			}
		}
		//constructorの処理
		this._event=(i_event)?i_event:null;
	};
	MiMicIGS.Rpc.prototype=
	{
		readyState:null,
		_createSocket:function(i_url){
			var sock=new AjaxSocket(i_url,"controlpoint");
			sock.readyState=3;//CLOSED;
			//websocketとの名前合わせ
			sock.onConnect=function(){};
			sock.onClose=function(){
				sock.readyState=3;//CLOSED;				
				if(sock.onclose){sock.onclose();}
			};
			sock.onOpen=function(){
				sock.readyState=1;//OPEN;
				if(sock.onopen){sock.onopen();}
			};
			sock.onMessage=function(d){if(sock.onmessage){sock.onmessage({data:d});__log("smsg");}};
			sock.onError=function(){
				sock.readyState=3;//CLOSED;
				__log(sock.onerror);if(sock.onerror){sock.onerror();}
			};
			return sock;
		}
	}
}());
(function()
{
	function __log(v){
		console.info(v);
	}
	var MI=MiMicJS;
	MiMicIGS.Mcu=function(i_url,i_handler)
	{
		//関数の継承
		for(var k in mbedJS.Mcu.prototype){
			this[k]=mbedJS.Mcu.prototype[k];
		}
		this._init(i_url,i_handler);
		__log("new");

	};
	var CLASS=MiMicIGS.Mcu;
	MiMicIGS.Mcu.prototype={
		//初期化処理
		_init:function(i_url,i_handler)
		{
			var _t=this;
			_t._lc=CLASS;
			_t._has_error=false;
			if(MI.isGenerator(i_handler)){_t._gen=i_handler;}
			else if(i_handler){_t._event=i_handler}
			
			_t._rpc=new MiMicIGS.Rpc({
				onOpen:function _Mcu_onOpen(){
					__log("open");
					if(_t._event.onNew){_t._event.onNew();}
					if(_t._gen){_t._gen.next(_t);}
					_t.lc=null;
				},
				onClose:function _Mcu_onClose(){
					__log("close");
					if(_t._lc==CLASS.close){
						if(_t._event.onClose){_t._event.onClose();}
					}else{
						if(_t._event.onError){_t._event.onError();}
					}
					if(_t._gen){
						_t._gen.next(_t);
					}			
					_t.lc=null;
				},
				onError:function _Mcu_onError()
				{
					__log("error");
					_t._has_error=true;
					if(_t._event.onError){_t._event.onError();}
					if(_t._gen && _t._lc){
						_t._gen.throw(new MI.MiMicException());
					}
					//@todo MCUにぶら下がってる全てのyieldに対してもExceptionの発生要請？
				}
			});
			//MCUへ接続
			this._rpc.open(i_url);
		}
	};

}());
