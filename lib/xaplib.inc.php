<?php
//global vars for heartbeat
$last_time=time()-XAPHEARTBEAT-1;
define('SEPARATOR',"----------------------------------------------------------------\n");

function xap_connect() {
	global $xap_sock_in,$xap_port;
	$xap_sock_in=socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
	socket_set_option($xap_sock_in, SOL_SOCKET, SO_RCVTIMEO, array('sec'=>floor(XAPRXTIMEOUT),'usec'=>(XAPRXTIMEOUT-floor(XAPRXTIMEOUT))*1000000));
	$xap_port=XAPPORT;
	if(!@socket_bind($xap_sock_in,'0.0.0.0',$xap_port)) {
		printf("Broadcast socket port %s in use\n",XAPPORT);
		print "Assuming a xAP hub is active\n";
		for($xap_port=XAPPORT+1;$xap_port<XAPPORT+1000;$xap_port++) {
			if(!@socket_bind($xap_sock_in,'127.0.0.1',$xap_port)) {
				printf("Socket port %s is in use\n",$xap_port);
			} else {
				printf("Discovered port %s\n",$xap_port);
				break;
			}
		}
	}
	printf("Listening on port %s\n",$xap_port);
	//$xap_sock_out=socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
	//socket_set_option($xap_sock_out, SOL_SOCKET, SO_BROADCAST, 1);
}

function xap_send($msg) {
	global $debug;
	if($xap_sock_out=socket_create(AF_INET, SOCK_DGRAM, SOL_UDP)) {
		@socket_set_option($xap_sock_out, SOL_SOCKET, SO_BROADCAST, 1);
		@socket_sendto($xap_sock_out, $msg, strlen($msg), 0, '255.255.255.255', XAPPORT);
		@socket_close($xap_sock_out);
		if($debug&XAP_DEBUG_ID) printf("%sSent at %s:\n%s%s",SEPARATOR,date('YmdHis'),$msg,SEPARATOR);
		return 1;
	} else return 0;
}

function xap_send_heartbeat($xapuid='',$xapsource='',$xapip='') {
	global $xap_port;
	if($xapuid=='') $xapuid=XAPUID;
	if($xapsource=='') $xapsource=XAPSOURCE;
	if($xapip!='') $xapip="ip=$xapip\n";
	$msg=sprintf("xap-hbeat\n{\nv=13\nhop=1\nuid=%s\nclass=xap-hbeat.alive\nsource=%s\ninterval=%s\nport=%s\n%s}\n",$xapuid,$xapsource,XAPHEARTBEAT,$xap_port,$xapip);
	return xap_send($msg);
}

function xap_send_heartbeat_stop($xapuid='',$xapsource='',$xapip='') {
	global $xap_port;
	if($xapuid=='') $xapuid=XAPUID;
	if($xapsource=='') $xapsource=XAPSOURCE;
	if(!is_array($xapuid)) $xapuid=array($xapuid);
	if(!is_array($xapsource)) $xapsource=array($xapsource);
	if($xapip!='') $xapip="ip=$xapip\n";
	for($i=0;$i<count($xapuid);$i++) {	
		$msg=sprintf("xap-hbeat\n{\nv=13\nhop=1\nuid=%s\nclass=xap-hbeat.stopped\nsource=%s\ninterval=%s\nport=%s\n%s}\n",$xapuid[$i],$xapsource[$i],XAPHEARTBEAT,$xap_port,$xapip);
		xap_send($msg);
	}
	return 1;
}

function xap_check_send_heartbeat($xapuid='',$xapsource='',$xapip='') { //$xapuid and $xapsource can be an array if we are more than one source
	global $last_time;
	//send xAP heartbeat periodically
	$t=microtime(true);
	if(floor($t)-$last_time>XAPHEARTBEAT) {
		if(!is_array($xapuid)) $xapuid=array($xapuid);
		if(!is_array($xapsource)) $xapsource=array($xapsource);
		for($i=0;$i<count($xapuid);$i++) {
			$xap_hb_sent=xap_send_heartbeat($xapuid[$i],$xapsource[$i],$xapip);
		}
		$last_time=floor($t);
	}
	return $t;
}

function xap_sendMsg($msg_class,$msg,$msg_target='',$msg_source='',$msg_uid='') {
	if($msg_source=='') $msg_source=XAPSOURCE;
	if($msg_uid=='') $msg_uid=XAPUID;
	if($msg_target!='') $msg_target=sprintf("target=%s\n",$msg_target);
	$m=sprintf("xap-header\n{\nv=13\nhop=1\nuid=%s\nclass=%s\nsource=%s\n%s}\n%s",$msg_uid,$msg_class,$msg_source,$msg_target,$msg);
  xap_send($m);
}

function xap_sendInfoMsg($msg, $target='', $source='', $uid='') {
	xap_sendMsg("xAPBSC.info",$msg,$target,$source,$uid);
}

function xap_sendEventMsg($msg, $target='', $source='', $uid='') {
	xap_sendMsg("xAPBSC.event",$msg,$target,$source,$uid);
}

function xap_sendCmdMsg($msg, $target='', $source='', $uid='') {
	xap_sendMsg("xAPBSC.cmd",$msg,$target,$source,$uid);
}

function xap_sendQueryMsg($msg, $target='', $source='', $uid='') {
	xap_sendMsg("xAPBSC.query",$msg,$target,$source,$uid);
}

function xap_send_switch_msg($switch_no,$state,$displayTxt='') {
  $msg="input.state\n{\nState=".$state."\n";
  if($displayTxt!='') $msg.="DisplayText=".$displayTxt."\n";
  $msg.="}\n";
  $source=xap_make_endpoint_source($switch_no);
  $uid=xap_make_endpoint_uid($switch_no);
  xap_sendEventMsg($msg,"",$source,$uid);
}

function xap_make_endpoint_uid($uid,$id) { //$id is a int from 0-254
	$uid=substr($uid,0,6).sprintf('%02X',$id+1);
	return $uid;
}

function xap_make_endpoint_source($source,$id,$name='') { //$id is a int from 0-254 or $name is a unique endpoint name
	if($name=='') $source=sprintf('%s:%02X',$source,$id+1);
	else $source=sprintf('%s:%s',$source,str_replace(' ','',$name));
	return $source;
}

function xap_listen(&$buffer) {
	global $debug,$xap_sock_in;
	$buffer=@socket_read($xap_sock_in,4096);
	if(strlen($buffer)) {
		$buffer=preg_replace("/^(}|{)([a-z]+)/i",'${1}'."\n".'${2}',$buffer);
		$buffer=preg_replace("/}$/i","}\n",$buffer);
		if($debug&XAP_DEBUG_ID) printf("%sReceived at %s:\n%s%s",SEPARATOR,date('YmdHis'),$buffer,SEPARATOR);
		$xa=explode("\n",$buffer);
		$b=array_pop($xa);
		if(count($xa)) {
			$i_state=0;
			$xap=array();
			$xapmsgid=0;
			while(count($xa)) {
				$d=trim(array_shift($xa));
				switch($i_state) {
					case 0:
						if(preg_match("/^xap\-/i",$d)) {
							$xap['HEADER']['TYPE']=$d;
							$xapmsgid=0;
							$i_state=1;
						} elseif(preg_match("/^[a-z0-9\_\-\ \.]+$/i",$d)) {
							$xap['MESSAGE'][$xapmsgid]['TYPE']=$d;
							$i_state=3;
						}
						break;
					case 1:
						if($d==='{') $i_state=2;
						break;
					case 2:
						if($d==='}') {
							$i_state=0;
						}	else {
							$t=explode("=",$d,2);
							$xap['HEADER']['DATA'][strtolower(trim($t[0]))]=trim($t[1]);
						}
						break;
					case 3:
						if($d==='{') $i_state=4;
						break;
					case 4:
						if($d==='}') {
							$i_state=0;
							$xapmsgid++;
						} else {
							$t=explode("=",$d,2);
							$xap['MESSAGE'][$xapmsgid]['DATA'][strtolower(trim($t[0]))]=trim($t[1]);
						}
						break;
				}
			}
			return $xap;
		}
	}
	return 0;
}
function xap_get_message(&$xap) {
	if(isset($xap['MESSAGE'])) return $xap['MESSAGE'];
	return array('TYPE'=>'');
}
function xap_get_class(&$xap) {
	if(isset($xap['HEADER']['DATA']['class'])) return $xap['HEADER']['DATA']['class'];
	return '';
}
function xap_get_source(&$xap) {
	if(isset($xap['HEADER']['DATA']['source'])) return $xap['HEADER']['DATA']['source'];
	return '';
}
function xap_get_target(&$xap) {
	if(isset($xap['HEADER']['DATA']['target'])) return $xap['HEADER']['DATA']['target'];
	return '';
}
function xap_get_uid(&$xap) {
	if(isset($xap['HEADER']['DATA']['uid'])) return $xap['HEADER']['DATA']['uid'];
	return '';
}
function xap_check_header(&$xap,$s) {
	return (isset($xap['HEADER']['TYPE']) and strcasecmp($xap['HEADER']['TYPE'],$s)===0);
}
function xap_check_class(&$xap,$s) {
	return (isset($xap['HEADER']['DATA']['class']) and strcasecmp($xap['HEADER']['DATA']['class'],$s)===0);
}
function xap_check_source(&$xap,$s) {
	if(!isset($xap['HEADER']['DATA']['source'])) return 0;
	return xap_check_address_match($s,$xap['HEADER']['DATA']['source']);
}
function xap_check_target(&$xap,$s) {
	if(!isset($xap['HEADER']['DATA']['target'])) return 0;
	return xap_check_address_match($s,$xap['HEADER']['DATA']['target']);
}

function xap_check_address_match($source,$target) {
	global $debug;
	if($debug&ADDR_DEBUG_ID) printf("Checking %s against %s : ",$source,$target);
	if($target==''or $source=='') return 0; //can't match empty source or target
	
	$s=explode(":",$source); //split up address and subaddress parts
	$s1=explode(".",$s[0]);
	if(isset($s[1])) $s2=explode(".",$s[1]); else $s2=array();
	$cs1=count($s1); $cs2=count($s2);

	$t=explode(":",$target);
	$t1=explode(".",$t[0]);
	if(isset($t[1])) $t2=explode(".",$t[1]); else $t2=array();
	
	$s1matches=0;
	$s2matches=0;
	
	for($i=0;$i<$cs1;$i++) {
		if(isset($t1[$i])) {
			if($t1[$i]=='>') {
				$s1matches=$cs1;
				$s2matches=$cs2;
				break;
			}
			if(strcasecmp($s1[$i],$t1[$i])==0 or $t1[$i]=='*') $s1matches++;
		}
	}
	if($s1matches==$cs1 and $s2matches==$cs2) {
		if($debug&ADDR_DEBUG_ID) print "True\n";
		return 1;
	}

	for($i=0;$i<$cs2;$i++) {
		if(isset($t2[$i])) {
			if($t2[$i]=='>') {
				$s2matches=$cs2;
				break;
			}
			if(strcasecmp($s2[$i],$t2[$i])==0 or $t2[$i]=='*') $s2matches++;
		}
	}	
	
	if($s1matches==$cs1 and $s2matches==$cs2) {
		if($debug&ADDR_DEBUG_ID) print "True\n";
		return 1;
	}
	
	if($debug&ADDR_DEBUG_ID) print "False\n";
	return 0;
}