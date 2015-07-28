<?php

function io_write_shared_memory($shm_id,$os,$is,$ip,$il) {
	global $debug;
	if($os) shmop_write($shm_id,$os,0);
	if($is) shmop_write($shm_id,$is,128);
	if($ip) shmop_write($shm_id,$ip,256);
	if($il) shmop_write($shm_id,$il,384);
	return 1;
}

function io_read_shared_memory($shm_id,&$out_states,&$in_states,&$in_programs,&$in_levels) {
	global $debug;
	if($s=shmop_read($shm_id,0,64*2*4)) {
		$out_states=substr($s,0,128);
		$in_states=substr($s,128,128);
		$in_programs=substr($s,256,128);
		$in_levels=substr($s,384,128);
		return 1;
	} else return 0;
}

function io_create_default_inames(&$names,&$types) {
	for($i=0;$i<64;$i++) {
		$names[$i+1]=sprintf("Switch%02d",$i+1);
		$types[$i+1]=SW_2P_M_CO; //default switch type to 2 pole momentary, centre off
	}
}
function io_create_default_onames(&$names,&$types) {
	for($i=0;$i<64;$i++) {
		$names[$i+1]=sprintf("Light%02d",$i+1);
		$types[$i+1]=OUT_DELAY1; //default to OUT_DELAY1, to provide a measure of relay protection
	}
}

function io_create_names_file($names_file,&$names,&$types) {
	if($fp=fopen($names_file,'wb')) {
		fwrite($fp,"# This is an id=>name mappings file\n");
		fwrite($fp,"ID\tTYPE\tNAME\n");
		for($i=0;$i<64;$i++) fwrite($fp,sprintf("%02d\t%02d\t%s\n",$i+1,$types[$i+1],$names[$i+1]));
		fclose($fp);
		return 1;
	}
	return 0;
}

function io_read_names_file($nfile,&$names,&$types) {
	if($f=@file($nfile,FILE_IGNORE_NEW_LINES)) {
		foreach($f as $l) {
			if(preg_match_all("/^([0-9]{2})[\t]+([0-9]{2})[\t]+([a-z0-9\-\_\.\ ]+)$/i",$l,$m)) {
				$names[sprintf("%d",$m[1][0])]=$m[3][0];
				$types[sprintf("%d",$m[1][0])]=$m[2][0];
			}
		}
		return count($names);
	}
	return 0;
}



function io_create_state_file($state_file,&$out_states,&$in_states,&$in_programs,&$in_levels) {
	$out_states='00000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000';
	$in_states='00000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000';
	$in_programs='00000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000';
	$in_levels='A0A0A0A0A0A0A0A0A0A0A0A0A0A0A0A0A0A0A0A0A0A0A0A0A0A0A0A0A0A0A0A0A0A0A0A0A0A0A0A0A0A0A0A0A0A0A0A0A0A0A0A0A0A0A0A0A0A0A0A0A0A0A0A0';
	return io_write_state_file($state_file,$out_states,$in_states,$in_programs,$in_levels);
}

function io_read_state_file($state_file,&$out_states,&$in_states,&$in_programs,&$in_levels) {
	if(!file_exists($state_file)) return 0;
	$f=@file($state_file,FILE_IGNORE_NEW_LINES);
	$i=0;
	foreach($f as $l) {
		if(preg_match("/^[0-9a-f]{128}$/i",$l)) {
			$i++;
			switch($i) {
				case 1:
					$out_states=$l;
					break;
				case 2:
					$in_states=$l;
					break;
				case 3:
					$in_programs=$l;
					break;
				case 4:
					$in_levels=$l;
					break;
			}
		}
	}
	if($i==4) return 1;
	return 0;
}

function io_write_state_file($state_file,&$out_states,&$in_states,&$in_programs,&$in_levels) {
	if($fp=fopen($state_file,'wb')) {
		fwrite($fp,"# this is the persistence file for hac_io.php\n");
		fwrite($fp,"# 64 OUTPUTS: 0x00 or 0xFF = off or on\n");
		fwrite($fp,$out_states);
		fwrite($fp,"\n# 64 SWITCH INPUTS: 0x00 or 0xFF = off or on\n");
		fwrite($fp,$in_states);
		fwrite($fp,"\n# 64 SWITCH INPUTS PROG #ID: 0x00 to 0xFF = 0-255\n");
		fwrite($fp,$in_programs);
		fwrite($fp,"\n# 64 SWITCH INPUTS LEVEL 0x00 to 0xFF = 0-255\n");
		fwrite($fp,$in_levels);
		fwrite($fp,"\n");
		fclose($fp);
		return 1;
	}
	return 0;
}
