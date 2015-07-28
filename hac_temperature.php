#!/usr/bin/php
<?php

define ('XAPUID','FF10A200');
define ('XAPSOURCE','Hengwm.HAC.Temperature');
define ('XAPHEARTBEAT',60);
define ('XAPPORT',3639);
define ('XAPRXTIMEOUT',5.0);

# debug values (can be ORed together)
define ('XAP_DEBUG_ID',2);

include("lib/xaplib.inc.php");

define ('TEMPPOLLTIME',60); //how often to read temp sensors
define ('SENSORPATH','/sys/devices/w1_bus_master1');
define ('SENSORLIST',SENSORPATH.'/w1_master_slaves');

define ('SENSORMAPFILE',dirname(__FILE__).'/hac_w1_sensor_map.txt');

date_default_timezone_set('Europe/London');
ob_implicit_flush ();
set_time_limit (0);
// signal handling
declare(ticks=1); $must_exit=0;
pcntl_signal(SIGTERM, "signal_handler");
pcntl_signal(SIGINT, "signal_handler");

$argc=$_SERVER["argc"];
$argv=$_SERVER["argv"]; //$argv is an array
if($argc==0) error(usage());
$args=parse_args($argc,$argv);
if(isset($args['d'])) $debug=$args['d'];
elseif(isset($args['debug'])) $debug=$args['debug'];
else $debug=0;

if(isset($args['l']) or isset($args['list-sensors'])) {
	$debug=1;
	$s=enumerate_w1_sensors(1,1);
	passthru("cat ".SENSORMAPFILE);
	exit(0);
}

if(isset($args['r']) or isset($args['remap-sensors'])) {
	if(isset($args['k']) or isset($args['keep-names'])) $keep=1; else $keep=0;
	$debug=1;
	$s=enumerate_w1_sensors(0,$keep);
	passthru("cat ".SENSORMAPFILE);
	exit(0);
}



$ltt=time()-TEMPPOLLTIME-1;

xap_connect();

while(1) {
	if($must_exit) break;

	//send xAP heartbeat periodically
	$t=floor(xap_check_send_heartbeat()); //and return time in secs

	if($t-$ltt>TEMPPOLLTIME) {
		$tsent=send_temperatures();
		$ltt=$t;
	}

	sleep(5);

}

//------------------------------------------------------
function send_temperatures() {
	global $debug;
	$s=enumerate_w1_sensors();
	if(is_array($s)) {
		foreach($s as $k=>$v) { //$k=xap_id $v=sensor metadata
			if($v['TYPE']==28) {
				$id=$v['TYPE'].'-'.$v['SENSOR_ID'];
				if($debug) printf("Reading 1 wire probe with ID %s: ",$id);
				$cmd='cat '.SENSORPATH.'/'.$id.'/w1_slave';
				$r=explode("\n",`$cmd`);
				if(substr($r[0],-3)=='YES') {
					$t=explode("=",$r[1]);
					$temp=sprintf("%.03f",$t[1]/1000);
					if($debug) printf("%s degC\n",$temp);
					$msg=sprintf("info.temperature\n{\nname=%s\ndatetime=%s\nunit=c\nvalue=%s\nsensorId=%s\n}\n",XAPSOURCE.'.'.$v['NAME'],date('YmdHis'),$temp,$id);
					xap_sendMsg('xAPTSC.info',$msg,'',xap_make_endpoint_source($k),xap_make_endpoint_uid($k));
				}
			}
		}
	}
	return 1;
}

function enumerate_w1_sensors($keep_existing=1,$keep_names=1) {
	global $debug;
	if(!file_exists(SENSORMAPFILE)) file_put_contents(SENSORMAPFILE,'');//create file if not exists
	$map=@file(SENSORMAPFILE,FILE_IGNORE_NEW_LINES);
	//build a sensor list from the history file
	$sensors=array();
	if($keep_existing) { //keep the mappings and names already in the list or start again?
		foreach($map as $l){
			if(preg_match_all("/^([0-9]+)[\t]+([0-9]{2})[\t]+([0-9a-f]{12})[\t]+(YES|NO)[\t]+([a-z0-9\ \_\-\.]+)$/i",$l,$m)) {
				$sensors[$m[1][0]]=array('ID'=>$m[1][0],'TYPE'=>$m[2][0],'SENSOR_ID'=>$m[3][0],'AVAILABLE'=>'NO','NAME'=>$m[5][0]);
			}
		}
	} else { //start again
		if($keep_names) { //but keep names
			foreach($map as $l){
				if(preg_match_all("/^([0-9]+)[\t]+([0-9]{2})[\t]+([0-9a-f]{12})[\t]+(YES|NO)[\t]+([a-z0-9\ \_\-\.]+)$/i",$l,$m)) {
					$names[$m[2][0].'-'.$m[3][0]]=$m[5][0];
				}
			}
		}
	}
	$sensor_count=count($sensors);

	//now check whats is actually present and update the file if necessary
	$sl=@file(SENSORLIST,FILE_IGNORE_NEW_LINES);
	if(is_array($sl)) {
		foreach($sl as $f) {
			if(preg_match_all("/^([0-9]{2})\-([0-9a-f]{12})$/i",$f,$m)) {
				$type=$m[1][0];
				$sensor_id=$m[2][0];
				$cid=$type.'-'.$sensor_id;
				$not_found=1;
				if($sensor_count) {
					for($i=0;$i<$sensor_count;$i++) {
						if($sensors[$i]['TYPE']==$type and $sensors[$i]['SENSOR_ID']==$sensor_id) {
							$sensors[$i]['AVAILABLE']='YES';
							$not_found=0;
						}
					}
				}
				if($not_found) {
					$sensor_name='NewSensor'.$cid;
					if($keep_existing===0 and $keep_names and isset($names[$cid])) $sensor_name=$names[$cid];
					$sensors[$sensor_count]=array('ID'=>$sensor_count,'TYPE'=>$type,'SENSOR_ID'=>$sensor_id,'NAME'=>$sensor_name,'AVAILABLE'=>'YES');
					$sensor_count++;
					if($debug) printf("Found new sensor Type: %s, ID: %s, Name: '%s'\n",$type,$sensor_id,$sensor_name);
				}
			}
		}
	} else if($debug) print "No Sensors Found!\n";
	if($fp=@fopen(SENSORMAPFILE,'wb')) {
		fwrite($fp,"ID\tTYPE\tSENSOR_ID\tAVAILABLE\tNAME\n");
		for($i=0;$i<$sensor_count;$i++) fwrite($fp,sprintf("%s\t%s\t%s\t%s\t\t%s\n",$sensors[$i]['ID'],$sensors[$i]['TYPE'],$sensors[$i]['SENSOR_ID'],($sensors[$i]['AVAILABLE']?'YES':'NO'),$sensors[$i]['NAME']));
		fclose($fp);
	}
	return $sensors;
}

function signal_handler($signal) {
	global $must_exit;
	switch($signal) {
		case SIGTERM:
			$must_exit='SIGTERM';
			break;
		case SIGKILL:
			$must_exit='SIGKILL';
			break;
		case SIGINT:
			$must_exit='SIGINT';
			break;
	}
	print $must_exit."\n";
}

function parse_args(&$argc,&$argv) {
	$argv[]="";
	$argv[]="";
	$args=array();
	//build a hashed array of all the arguments
	$i=1; $ov=0;
	while ($i<$argc) {
		if (substr($argv[$i],0,2)=="--") $a=substr($argv[$i++],2);
		elseif (substr($argv[$i],0,1)=="-") $a=substr($argv[$i++],1);
		else $a=$ov++;
		if (strpos($a,"=") >0) {
			$tmp=explode("=",$a);
			$args[$tmp[0]]=$tmp[1];
		} else {
			if (substr($argv[$i],0,1)=="-" or $i==$argc) $v=1;
			else $v=$argv[$i++];
			$args[$a]=$v;
		}
	}
	return $args;
}
