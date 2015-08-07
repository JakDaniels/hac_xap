#!/usr/bin/php
<?php

include("lib/hac_sensor_defines.inc.php");
include("lib/functions.inc.php");
include("lib/xaplib.inc.php");
logformat("hac_sensor is starting....\n");

$mods=explode(",",KERNELMODULES);
foreach($mods as $m) `rmmod $m`;
foreach($mods as $m) `modprobe $m`;

define ('SENSORMAPFILE',dirname(__FILE__).'/etc/hac_w1_sensor_map.txt');

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

$ltt=time()-SENSORPOLLTIME-1;

xap_connect();

while(1) {
	if($must_exit) break;

	//send xAP heartbeat periodically
	$t=floor(xap_check_send_heartbeat()); //and return time in secs

	if($t-$ltt>=SENSORPOLLTIME) {
		$tsent=send_data();
		$ltt=$t;
	}

	if($must_exit) break;
	sleep(1);
	if($must_exit) break;
	sleep(1);
	if($must_exit) break;
	sleep(1);
	if($must_exit) break;
	sleep(1);
	if($must_exit) break;
	sleep(1);
}
logformat("hac_sensor exiting cleanly.\n");

//------------------------------------------------------
function send_data() {
	global $debug;
	$s=enumerate_w1_sensors();
	if(is_array($s)) {
		foreach($s as $k=>$v) { //$k=xap_id $v=sensor metadata
			if($v['AVAILABLE']=='YES') {

				if($v['TYPE']==28) { //1 wire temperature sensor
					$id=$v['TYPE'].'-'.$v['SENSOR_ID'];
					logformat(sprintf("Reading 1 wire temperature sensor with ID: '%s' and NAME: '%s'.\n",$id,$v['NAME']));
					$cmd='cat '.SENSORPATH.'/'.$id.'/w1_slave';
					$r=explode("\n",`$cmd`);
					if(substr($r[0],-3)=='YES') {
						$t=explode("=",$r[1]);
						$temp=sprintf("%.03f",$t[1]/1000);
						logformat(sprintf("Id=%s,Name=%s,Data=%s°C\n",$id,$v['NAME'],$temp));
						$msg=sprintf("info.temperature\n{\nname=%s\ndatetime=%s\nunit=c\nvalue=%s\nsensorId=%s\n}\n",XAPSOURCE.'.'.$v['NAME'],date('YmdHis'),$temp,$id);
						xap_sendMsg('xAPTSC.info',$msg,'',xap_make_endpoint_source(XAPSOURCE,$k),xap_make_endpoint_uid(XAPUID,$k));
					}
				}

			} else logformat(sprintf("Skipping 1 wire sensor with ID: '%s-%s' and NAME: '%s'.\n",$v['TYPE'],$v['SENSOR_ID'],$v['NAME']));
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
							$id=$sensors[$i]['TYPE'].'-'.$sensors[$i]['SENSOR_ID'];
							if(file_exists(SENSORPATH.'/'.$id.'/w1_slave')) $sensors[$i]['AVAILABLE']='YES';
							else $sensors[$i]['AVAILABLE']='NO';
							$not_found=0;
						}
					}
				}
				if($not_found) {
					$sensor_name='NewSensor'.$cid;
					if($keep_existing===0 and $keep_names and isset($names[$cid])) $sensor_name=$names[$cid];
					$sensors[$sensor_count]=array('ID'=>$sensor_count,'TYPE'=>$type,'SENSOR_ID'=>$sensor_id,'NAME'=>$sensor_name,'AVAILABLE'=>'YES');
					$sensor_count++;
					logformat(sprintf("Found new sensor Type: %s, ID: %s, Name: '%s'\n",$type,$sensor_id,$sensor_name));
				}
			}
		}
	} else logformat("No Sensors Found!\n");
	if($fp=@fopen(SENSORMAPFILE,'wb')) {
		fwrite($fp,"ID\tTYPE\tSENSOR_ID\tAVAILABLE\tNAME\n");
		for($i=0;$i<$sensor_count;$i++) fwrite($fp,sprintf("%s\t%s\t%s\t%s\t\t%s\n",$sensors[$i]['ID'],$sensors[$i]['TYPE'],$sensors[$i]['SENSOR_ID'],($sensors[$i]['AVAILABLE']?'YES':'NO'),$sensors[$i]['NAME']));
		fclose($fp);
	}
	return $sensors;
}
