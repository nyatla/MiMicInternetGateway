
/**
 * mbedへの接続テスト
 */
function testMbedJs(i_ws_url,i_cb)
{
	var mcu=new Mcu(i_ws_url,
	{
		onNew:function(){
			var ret=null;
			mcu.getInfo(
				function(v){
					ret=v;
					mcu.close();
				});
		},
		onError:function(){
			cb(null);
		},
		onClose:function(){
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
 * onOpen
 * CPからの接続を検出した。
 * onClose
 * CPからの切断を検出した。
 * onError
 * サービスでエラーが発生した。(オフラインになった)
 * onWsError
 * Notify:WSセッションで復帰可能なエラーが発生した(CPは切断される)
 * onWsClose
 * Notify:WSセッションで復帰可能なクローズが発生した(CPは切断される)
 * onWsRx
 * Notify:WSセッションで受信イベントが発生した
 */
function Content()
{
	var _t=this;
	var mjs=null;
	var ws=null;
	/**
	 * Websocketの全てのイベントをキャンセルして利用不能にする。
	 */
	function shutdownWs()
	{
		if(ws){
			ws.onopen=function(){ws.close();};
			ws.onmessage=function(){ws.close();};
			ws.onclose=function(){ws=null;};
			ws.onerror=function(){ws=null;};
			ws.close();
			ws=null;
		}
	}
	this.start=function(i_ws_url){
		if(mjs){
			throw "already started!"
		}
		//フルパス再構築
		var full_path=location.href;
		full_path=full_path.slice(0,full_path.lastIndexOf("/")+1);
		full_path+="ajaxsocket.php";
		//AjaxSocketの開始
		mjs=new AjaxSocket(full_path,"endpoint");
		mjs.onConnect=function(v){
			__log("mjs.onConnect");
			if(_t.onOnline){_t.onOnline(v);}
		};
		mjs.onOpen=function(){
			__log("mjs.onOpen");
			if(_t.onOpen){_t.onOpen();}
			
			ws=new WebSocket(i_ws_url);
			ws.onclose = function(){
				__log("mjs.onClose");
				//サービス有効ならコントロールポイントの接続を破棄
				if(mjs){
					//WebsocketServerから切断された場合
					mjs.resetPeer();
				}
				//クローズ通知
				if(_t.onWsClose){_t.onWsClose();}
			};
			ws.onerror = function(){
				__log("mjs.onError");
				//サービス有効ならコントロールポイントの接続を破棄
				if(mjs){
					//WebsocketServerから切断された場合
					mjs.resetPeer();
				}
				ws=null;
				//エラー通知
				if(_t.onWsError){_t.onWsError();}
			};
			ws.onopen = function(){
				if(_t.onWsOpen){_t.onWsOnen();}
			}
			ws.onmessage=function(m){
				__log("ws:"+m.data);
				mjs.send(m.data);
				//受信通知
				if(_t.onWsTx){_t.onWsTx(m.data);}
			}
		};
		mjs.onError=function(){
			//サービスとの接続エラー
			__log("mjs.onError");
			shutdownWs();
			if(_t.onError){_t.onError();}
		};
		mjs.onClose=function(){
			//CPからの切断要求
			__log("mjs.onClose");
			shutdownWs();
			if(_t.onClose){_t.onClose();}
		};
		mjs.onDisconnect=function(){
			//サービスとの切断
			__log("mjs.onDisconnect");
			shutdownWs();
			if(_t.onOffline){_t.onOffline();}
		};
		
		mjs.onMessage=function(m){
			__log("mjs.onMessage:"+m);
			if(ws){
				if(_t.onWsRx){_t.onWsRx(m);}				
				ws.send(m);
			}
		}
	}
	this.stop=function(){
		if(mjs==null){
			throw "already stopped!"
		}
		mjs.close();//ERRORかCLOSEが出るからそこでのソケットシャットダウンに期待
		//サービス参照の無効化
		mjs=null;
	}
}
Content.prototype={
	_status:null,
};
