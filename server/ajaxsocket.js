function __log(v){
//	console.info(v);
}
var AjaxSoxcket=null;
(function(){


/**
 * 再送処理付きのAjax
 */
function Ajax(i_url,i_cb,i_delay)
{
	var AJAX_RETRY=1000;
	var retry=3;
	function f(){
		var xhr=null;
	    var tid=null;
		function doAjex()
		{
		    xhr = XMLHttpRequest ? new XMLHttpRequest() : new XDomainRequest();
		    function callError()
		    {
		    	if((retry--)>0){
			    	tid=setTimeout(doAjex,AJAX_RETRY);
		    	}else{
//		    		__log("ajax:error");
		    		if(i_cb.onError){i_cb.onError();}
		    	}
		    }
		    xhr.onload=function(){
		    	tid=null;
		    	if(i_cb.onSuccess){
		    		if(xhr.status==200){
//		    			__log("ajax:success");		    			
		        		i_cb.onSuccess(xhr.responseText);
		    		}else{
		    			callError();
		    		}
		    	}
		    }
		    xhr.onerror=function(){
		    	tid=null;
				callError();
		    }
		    xhr.open("GET",i_url,true);
		    xhr.send(null);
		}
	    this.cancel=function(){
	    	if(tid){
	    		clearTimeout(tid);
	    		tid=null;
	    	}
	    	if(xhr){xhr.abort();}
	    }
	    if(!i_delay){
	    	doAjex();
	    }else{
	    	tid=setTimeout(function(){doAjex();},i_delay);
	    }
	}
	return new f();
}
/**
 * @callback onConnect
 * endpoint
 * 	中継サービスとの接続に成功したとき
 * controlpoint
 * 	中継サービスとの接続に成功したとき
 * @callback onClose
 * endpoint
 * 	CPの切断を検出したとき
 * controlpoint
 * 	closeが完了したとき
 * @callback onOpen
 * endpoint
 * 	CPの接続を検出したとき
 * controlpoint
 * 	中継サービスとの接続に成功したとき(onConnectの次に発生)
 * @callback onMessage
 * endpoint/controlpoint
 * メッセージを受信したとき
 * @callback onError
 * endpoint/controlpoint
 * 	通信エラーが発生したとき
 */
AjaxSocket=function(i_url,i_type)
{
	var ENDPOINT_REFRESH=1000;
	var CTRLPOINT_REFRESH=1000;
	var _t=this;
	_t._type=i_type;
	_t._tx_q="";
	if(_t._type=="endpoint"){
		_t._url=i_url;		
		_t._last_ajax=Ajax(_t._url+"?cmd=listen",
		{
			onSuccess:function(v){
				var last_ckey="";
				var json=eval('('+v+')');
				_t._url=_t._url+"?sid="+json.sid+"&key="+json.key+"&";
				_t._connected=true;
				if(_t.onConnect){_t.onConnect(i_url+"?sid="+json.sid+"&cmd=connect");}
				var rx_q="";
				function f(i_delay){
					var tx=_t._tx_q;
					_t._tx_q="";
					return new Ajax(_t._url+"cmd=sync&payload="+encodeURIComponent(tx),
					{
						onSuccess:function(v){
							var json=eval('('+v+')');
							__log("Ep:"+json.payload);
							//CtrolPointキーのチェック
							if(json.ckey.length==0){
								if(last_ckey.length==0){
									//新旧キー無効なら何もしない
								}else{
									//新キー無効、旧キー有効ならCLOSE
									_t._opened=false;
									if(_t.onClose){_t.onClose();}
								}
							}else{
								if(last_ckey.length==0){
									//新キー有効、旧キー無効ならOPEN
									_t._opened=true;//OPEN
									if(_t.onOpen){_t.onOpen();}
									//CtrlPointとの時差でExを受信してることがある。
									rx_q+=json.payload;
								}else{
									if(last_ckey==json.ckey){
										//新旧キー同一かつ新キー有効ならMessage
										rx_q+=json.payload;
										if(rx_q.length>0){
											if(_t.onMessage){_t.onMessage(rx_q);}
										}
										rx_q="";
									}else{
										//エラーじゃない？ありえないよ？
										alert("ERROR? ARIENE-YO!");
									}
								}
							}
							last_ckey=json.ckey;
							_t.last_ajax=f(ENDPOINT_REFRESH);
						},
						onError:function(){
							_t.last_ajax=null;
							_t._connected=false;
							_t._opened=false;
							if(_t.onError){_t.onError();}
						}
					},i_delay);
				}
				_t.last_ajax=f();
			},
			onError:function(){
				_t.last_ajax=null;
				if(_t.onError){_t.onError();}
			}
		});
	}else{
		//URIがそのままくるからばらす。
		_t._url=i_url.split('?')[0];
		_t._last_ajax=Ajax(i_url,
		{
			onSuccess:function(v){
				_t._tx_q="";		
				var json=eval('('+v+')');
				_t._connected=true;
				_t._opened=true;
				_t._url=_t._url+"?sid="+json.sid+"&key="+json.key;
				function f(i_delay){
					var tx=_t._tx_q;
					_t._tx_q="";
					__log("sync...");
					return new Ajax(_t._url+"&cmd=sync&payload="+encodeURIComponent(tx),
					{
						onSuccess:function(v){
							var json=eval('('+v+')');
							__log("Cp:"+json.payload);
							if(_t.onMessage){_t.onMessage(json.payload);}
							_t.last_ajax=f(CTRLPOINT_REFRESH);
						},
						onError:function(){
							__log("Cp:Error1");
							_t.last_ajax=null;
							_t._connected=false;
							_t._opened=false;
							if(_t.onError){_t.onError();}
						}
					},i_delay);
				}
				_t.last_ajax=f();
				if(_t.onConnect){_t.onConnect();}
				if(_t.onOpen){_t.onOpen();}
			},
			onError:function(){
				__log("Cp:Error2");
				_t.last_ajax=null;
				if(_t.onError){_t.onError();}
			}
		});
	}

}
AjaxSocket.prototype=
{
	_connected:false,
	//Peerと接続している場合true
	_opend:false,
	_type:null,
	_tx_q:null,
	_last_ajax:null,
	_url:null,
	//ソケットサーバに接続した
	onConnect:null,
	//メッセージが到達した。
	onDisconnect:null,
	//クライアントが接続した
	onOpen:null,
	//クライアントが切断した
	onClose:null,
	//エラーで通信が維持できない
	onMessage:null,
	//ソケットサーバから切断した
	onError:null,
	//AjaxSocketのクローズ
	close:function(){
		var _t=this;
		__log(_t.last_ajax);
		if(_t.last_ajax){
			_t.last_ajax.cancel();
		}
		if(this._connected){
			var a=new Ajax(_t._url+"&cmd=close",{
				onSuccess:function(){
					_t._opened=false;
					if(_t._type=="endpoint"){
						//nothing to do
					}else{
						_t._connected=false;
						if(_t.onClose){_t.onClose();}
					}
					if(_t.onDisconnect){_t.onDisconnect();}
				},
				onError:function(){
					_t._opened=false;
					if(_t.onError){_t.onError();}
				}
			});
		}
		this._connected=false;
	},
	/**
	 * Peerコネクションを強制切断(endpointのみ)
	 * @callback onClose
	 * Peerのクローズに成功
	 * @callback onError
	 * 通信に失敗
	 */
	resetPeer:function(){
		var _t=this;
		if(_t._type!="endpoint"){
			return;
		}
		if(_t.last_ajax){
			_t.last_ajax.cancel();
		}
		if(_t._connected){
			var a=new Ajax(_t._url+"&cmd=resetpeer",{
				onSuccess:function(){
					if(_t.onClose){_t.onClose();}
				},
				onError:function(){
					//失敗時は切断要求
					_t.close();
				}
			});
		}
		_t._opened=false;
	},	
	send:function(v){
		if(this._opened){
			__log("add:"+this._tx_q);
			this._tx_q+=v;
			return true;
		}
		return false;
	},

}
}());
