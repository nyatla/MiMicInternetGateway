var IGS_URL="ws://localhost:9300/endpt";
function __log(m)
{
//	console.info(m);
}
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
function IgsJson(i_src)
{
	var j=eval('('+i_src+')');
	this.json=j;
	this.src=i_src;
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
	var ws_igs=null;
	var ws=null;
	/**
	 * Websocketの全てのイベントをキャンセルして利用不能にする。
	 */
	function shutdownWs(i_ws)
	{
		if(i_ws){
			i_ws.onopen=function(){i_ws.close();};
			i_ws.onmessage=function(){i_ws.close();};
			i_ws.onclose=function(){i_ws=null;};
			i_ws.onerror=function(){i_ws=null;};
			i_ws.close();
		}
	}
	this.start=function(i_device_url){
		if(ws_igs){
			throw "already started!"
		}
		//フルパス再構築
		var full_path=location.href;
		//IgsWebsocketの開始
		
		
		ws_igs=new WebSocket(IGS_URL);
		ws_igs.onopen=function(){
			__log("igs.onopen");
		}
		ws_igs.onclose=function(){
			//サービスとの切断
			__log("igs.close");
			shutdownWs(ws_igs);
			ws_igs=null;
			if(_t.onOffline){_t.onOffline();}			
		}
		ws_igs.onerror=function(){
			//サービスとの接続エラー
			__log("igs.error");
			shutdownWs(ws_igs);
			ws_igs=null;
			if(_t.onError){_t.onError();}			
		}
		var msg_q=null;
		function onmessage_2nd(i_m)
		{
			__log("igs.onmessage2:"+i_m.data);
			//メッセージリレー開始
			var json=eval('('+i_m.data+')');
			switch(json.method){
			case 'miigs:endpt:hello':
				msg_q="";//qの初期化
				ws=new WebSocket(i_device_url);
				ws.onopen = function(){
					__log("ws.onopen");
					if(_t.onWsOpen){_t.onWsOpen();}
					if(msg_q!=null){
						ws.send(msg_q);
						if(_t.onWsRx){_t.onWsRx(msg_q);}						
						msg_q=null;
					}
				}
				ws.onclose = function(){
					__log("ws.onclose");
					if(ws_igs){
						ws_igs.send('{"jsonrpc":"2.0","method":"miigs:endpt:ondevclose","params":[],"id":-1}');
					}
					ws=null;
					if(_t.onWsClose){_t.onWsClose();}
				};
				ws.onerror = function(){
					__log("ws.onclose");
					if(ws_igs){
						ws_igs.send('{"jsonrpc":"2.0","method":"miigs:endpt:ondeverror","params":[],"id":-1}');
					}
					ws=null;
					if(_t.onWsClose){_t.onWsClose();}
				};
				ws.onmessage=function(m){
					__log("ws.onmessage");
					if(ws_igs){
						ws_igs.send(m.data);
					}
					//受信通知
					if(_t.onWsTx){_t.onWsTx(m.data);}
				}		
				return;
			case 'miigs:endpt:byebye':					
				if(ws){
					ws.close();
				}
				ws=null;
				return;
			}
			if(ws){
				if(msg_q!=null){
					__log("qing:"+i_m.data);
					msg_q+=i_m.data;
				}else{
					__log("sendto device"+i_m.data);
					ws.send(i_m.data);
					if(_t.onWsRx){_t.onWsRx(i_m.data);}
				}
			}
		}
		
		function ommessage_1st(i_m){
			//開始時のnotify(ready)
			__log("igs.onmessage:"+i_m.data);
			var json=eval('('+i_m.data+')');
			if(json.method!="miigs:endpt:ready"){
				shutdownWs(ws_igs);
				ws_igs=null;
				if(_t.onOffline){_t.onOffline();}
				return;
			}
			var msg_q=null;//helloの後接続されるまでのキュー
			ws_igs.onmessage=onmessage_2nd;
			if(_t.onOnline){_t.onOnline(json.params[0]);}
		}
		ws_igs.onmessage=ommessage_1st;
	}
	this.stop=function(){
		if(!ws_igs){
			throw "already stopped!"
		}
		ws_igs.close();//ERRORかCLOSEが出るからそこでのソケットシャットダウンに期待
		//サービス参照の無効化
		ws_igs=null;
	}
}
Content.prototype={
	_status:null,
};
