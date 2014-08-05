
function AjaxWs()
{
}
AjaxWs.prototype=
{
	onopen:function(){
		
	},
	onerror:function(){
		
	}
}
/**
 * mbedへの接続テスト
 */
function testMbedJs(i_ws_url,i_cb)
{
	var mcu=new Mcu(i_ws_url,
	{
		var ret=null;
		onNew(){
			mcu.getInfo(
				function(v){
					ret=v;
					mcu.close();
				});
		},
		onError(){
			cb(null);
		},
		onClose(){
			cb(ret);
		}
	});
}

/**
 * +メソッド
 * +start
 * +stop
 * +イベント
 * onOnline
 * サービスがオンラインになった。
 * onOffline
 * サービスがオフラインになった。
 * onWsError
 * WSセッションで復帰可能なエラーが発生した
 * onWsClose
 * WSセッションで復帰可能なクローズが発生した
 * onWsOpen
 * WSセッションが開かれた
 * onWsRx
 * WSセッションで受信イベントが発生した
 */
function Content()
{
	var _t=this;
	var mjs=null;
	var ws=null;
	/**
	 * Websocketの全てのイベントをキャンセルして利用不能にする。
	 */
	shutdownWs()
	{
		if(ws){
			ws.onopen=function(){ws.close();};
			ws.onmessage=function(){ws.close();};
			ws.onclose=function(){ws=null;};
			ws.onerror=function(){ws=null;};
			ws=null;
		}
	}
	this.start=function(i_ws_url){
		if(mjs){
			throw "already started!"
		}
		//AjaxSocketの開始
		mjs=new AjaxSocket("./ajaxsocket.php","endpoint");
		mjs.onConnect=function(v){
			__log("mjs.onConnect");
			if(_t.onOnline){_t.onOnline(v);}
		};
		mjs.onOpen=function(){
			__log("mjs.onOpen");
			if(_t.onOffLine){_t.onOffLine();}
			
			ws=new WebSocket(i_ws_url);
			ws.onclose = function(){
				//クローズ→ws再接続待機
				if(_t.onWsClose){_t.onWsClose();}
			};
			ws.onerror = function(){
				ws=null;
				//エラー発生→ws再接続待機
				if(_t.onWsError){_t.onWsError();}
			};
			ws.onopen = function(){
				if(_t.onWsOpen){_t.onWsOnen();}
			}
			ws.onmessage=function(m){				
				if(_t.onWsRx){_t.onWsRx();}
			}
		};
		mjs.onError=function(){
			__log("mjs.onError");
			shutdownWs();
			if(_t.onOffLine){_t.onOffLine();}
		};
		mjs.onClose=function(){
			__log("mjs.onClose");
			shutdownWs();
			if(_t.onOffLine){_t.onOffLine();}
		};
		mjs.onDissconnect=function(){
			__log("mjs.onDisconnect");
			shutdownWs();
			if(_t.onOffLine){_t.onOffLine();}
		};
		
		mjs.onMessage=function(m){
			__log("mjs.onMessage:"+m);
			if(ws){
				ws.send(m);
			}
		}
	}
	this.stop=function(){
		if(mjs==null){
			throw "already stopped!"
		}
		mjs.close();//ERRORかCLOSEが出るからそこでのソケットシャットダウンに期待
		mjs=null;
	}
}
Content.prototype={
	_status:null,
};
