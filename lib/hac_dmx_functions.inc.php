<?php

function dmx_write_shared_memory($shm_id,$di,$do1,$do2) {
	global $debug;
	if($di) shmop_write($shm_id,$di,0);
	if($do1) shmop_write($shm_id,$do1,256);
	if($do2) shmop_write($shm_id,$do2,512);
	return 1;
}

function dmx_read_shared_memory($shm_id,&$dmx_inputs,&$dmx_outputs1,&$dmx_outputs2) {
	global $debug;
	if($s=shmop_read($shm_id,0,128*2*3)) {
		$dmx_inputs=substr($s,0,256);
		$dmx_outputs1=substr($s,256,256);
		$dmx_outputs2=substr($s,512,256);
		return 1;
	} else return 0;
}

function dmx_create_default_inames(&$names) {
	for($i=0;$i<128;$i++) {
		$names[$i+1]=sprintf("ch%03d",$i+1);
	}
}
function dmx_create_default_onames(&$names) {
	for($i=0;$i<128;$i=$i+4) {
		$names[$i+1]=sprintf("ch%03d.0Red",$i+1);
		$names[$i+2]=sprintf("ch%03d.1Green",$i+1);
		$names[$i+3]=sprintf("ch%03d.2Blue",$i+1);
		$names[$i+4]=sprintf("ch%03d.3White",$i+1);
	}
}

function dmx_create_names_file($names_file,&$names) {
	if($fp=fopen($names_file,'wb')) {
		fwrite($fp,"# This is an id=>name mappings file\n");
		fwrite($fp,"ID\tNAME\n");
		for($i=0;$i<128;$i++) fwrite($fp,sprintf("%03d\t%s\n",$i+1,$names[$i+1]));
		fclose($fp);
		return 1;
	}
	return 0;
}

function dmx_read_names_file($nfile,&$names) {
	if($f=@file($nfile,FILE_IGNORE_NEW_LINES)) {
		foreach($f as $l) {
			if(preg_match_all("/^([0-9]{3})[\t]+([a-z0-9\-\_\.\ ]+)$/i",$l,$m)) {
				$names[sprintf("%d",$m[1][0])]=$m[2][0];
			}
		}
		return count($names);
	}
	return 0;
}

function dmx_create_state_file($dstate_file,&$dmx_inputs,&$dmx_outputs1,&$dmx_outputs2) {
	$dmx_inputs=str_pad('',256,'0');
	$dmx_outputs1=str_pad('',256,'0');
	$dmx_outputs2=str_pad('',256,'0');
	return dmx_write_state_file($dstate_file,$dmx_inputs,$dmx_outputs1,$dmx_outputs2);
}

function dmx_read_state_file($dstate_file,&$dmx_inputs,&$dmx_outputs1,&$dmx_outputs2) {
	if(!file_exists($dstate_file)) return 0;
	$f=@file($dstate_file,FILE_IGNORE_NEW_LINES);
	$i=0;
	foreach($f as $l) {
		if(preg_match("/^[0-9a-f]{256}$/i",$l)) {
			$i++;
			switch($i) {
				case 1:
					$dmx_inputs=$l;
					break;
				case 2:
					$dmx_outputs1=$l;
					break;					
				case 3:
					$dmx_outputs2=$l;
					break;
			}
		}
	}
	if($i==3) return 1;
	return 0;
}

function dmx_write_state_file($dstate_file,&$dmx_inputs,&$dmx_outputs1,&$dmx_outputs2) {
	if($fp=fopen($dstate_file,'wb')) {
		fwrite($fp,"# this is the persistence file for hac_dmx.php\n");
		fwrite($fp,"# 128CH INPUTS: 0x00 to 0xFF = 0-255\n");
		fwrite($fp,$dmx_inputs);
		fwrite($fp,"\n# 128CH OUTPUTS, UNIVERSE 1: 0x00 to 0xFF = 0-255\n");
		fwrite($fp,$dmx_outputs1);
		fwrite($fp,"\n# 128CH OUTPUTS, UNIVERSE 2: 0x00 to 0xFF = 0-255\n");
		fwrite($fp,$dmx_outputs2);
		fwrite($fp,"\n");
		fclose($fp);
		return 1;
	}
	return 0;
}

function get_dmx_level(&$dmx_ios,$i) {
	$l=hexdec(substr($dmx_ios,$i*2,2));
	return $l;
}

function set_dmx_level(&$dmx_ios,$i,$level) {
	global $debug;
	$l=hexdec(substr($dmx_ios,$i*2,2));
	if($l!==$level) $dmx_ios=substr_replace($dmx_ios,sprintf("%02X",$level),$i*2,2);
}

