<?php
//-----------------------------------------------------------------
function inc_program($i,&$in_states,&$in_programs,&$in_levels) {
	$ip=hexdec(substr($in_programs,$i*2,2))+1;
	if($ip>255) $ip=0; //programs rollover from 255 to 0
	$in_programs=substr_replace($in_programs,sprintf("%02X",$ip),$i*2,2);
	send_io_input_program_event_message($i,$in_states,$in_programs);
	return 1;
}

function zero_program($i,&$in_states,&$in_programs,&$in_levels) {
	$in_programs=substr_replace($in_programs,'00',$i*2,2);
	send_io_input_program_event_message($i,$in_states,$in_programs);
	return 1;
}

function inc_level($i,&$in_states,&$in_programs,&$in_levels) {
	$il=hexdec(substr($in_levels,$i*2,2));
	if($il<255) {
		$in_levels=substr_replace($in_levels,sprintf("%02X",++$il),$i*2,2);
		send_io_input_level_event_message($i,$in_states,$in_levels);
	}
	return 1;
}

function dec_level($i,&$in_states,&$in_programs,&$in_levels) {
	$il=hexdec(substr($in_levels,$i*2,2));
	if($il>0) {
		$in_levels=substr_replace($in_levels,sprintf("%02X",--$il),$i*2,2);
		send_io_input_level_event_message($i,$in_states,$in_levels);
	}
	return 1;
}

function full_level($i,&$in_states,&$in_programs,&$in_levels) {
	$in_levels=substr_replace($in_levels,'FF',$i*2,2);
	send_io_input_level_event_message($i,$in_states,$in_programs);
	return 1;
}

function switch_on($i,&$in_states,&$in_programs,&$in_levels) {
	$in_states=substr_replace($in_states,'FF',$i*2,2);
	send_io_input_level_event_message($i,$in_states,$in_levels);
	send_io_input_program_event_message($i,$in_states,$in_programs);
	return 1;
}

function switch_on_full_level($i,&$in_states,&$in_programs,&$in_levels) {
	$in_states=substr_replace($in_states,'FF',$i*2,2);
	$in_levels=substr_replace($in_levels,'FF',$i*2,2);
	send_io_input_level_event_message($i,$in_states,$in_levels);
	send_io_input_program_event_message($i,$in_states,$in_programs);
	return 1;
}

function switch_off($i,&$in_states,&$in_programs,&$in_levels) {
	$in_states=substr_replace($in_states,'00',$i*2,2);
	send_io_input_level_event_message($i,$in_states,$in_levels);
	send_io_input_program_event_message($i,$in_states,$in_programs);
	return 1;
}

function get_switch_state(&$in_states,$i) {
	$is=(substr($in_states,$i*2,2)=='00')?'Off':'On';
	return $is;
}

function set_switch_state(&$in_states,$i,$state) {
	global $debug;
	$is=substr($in_states,$i*2,2);
	if(strcasecmp($state,'On')==0) {
		$in_states=substr_replace($in_states,'FF',$i*2,2);
		if($debug&IO_DEBUG_ID) print "Setting Switch $i to 'On'\n";
	}
	if(strcasecmp($state,'Off')==0) {
		$in_states=substr_replace($in_states,'00',$i*2,2);
		if($debug&IO_DEBUG_ID) print "Setting Switch $i to 'Off'\n";
	}
	//other values have no effect
}

function get_switch_level(&$in_levels,$i) {
	$il=hexdec(substr($in_levels,$i*2,2));
	return $il;
}

function set_switch_level(&$in_levels,$i,$level) {
	global $debug;
	$il=hexdec(substr($in_levels,$i*2,2));
	if($il!==$level) {
		$in_levels=substr_replace($in_levels,sprintf("%02X",$level),$i*2,2);
		if($debug&IO_DEBUG_ID) printf("Setting Switch $i level to %d\n",$level);
	}
}

function get_switch_program(&$in_programs,$i) {
	$ip=hexdec(substr($in_programs,$i*2,2));
	return $ip;
}

function set_switch_program(&$in_programs,$i,$program) {
	global $debug;
	$ip=hexdec(substr($in_programs,$i*2,2));
	if($ip!==$program) {
		$in_programs=substr_replace($in_programs,sprintf("%02X",$program),$i*2,2);
		if($debug&IO_DEBUG_ID) printf("Setting Switch $i program to %d\n",$program);
	}
}

function is_switch_on(&$in_states,$i) {
	$is=substr($in_states,$i*2,2);
	if($is=='00') return 0;
	return 1;
}

function get_light_state(&$out_states,$i) {
	$os=(substr($out_states,$i*2,2)=='00')?'Off':'On';
	return $os;
}

function set_light_state(&$out_states,$i,$state) {
	global $debug;
	$os=substr($out_states,$i*2,2);
	if(strcasecmp($state,'On')==0) {
		$out_states=substr_replace($out_states,'FF',$i*2,2);
		if($debug&IO_DEBUG_ID) print "Setting Output $i to 'On'\n";
	}
	if(strcasecmp($state,'Off')==0) {
		$out_states=substr_replace($out_states,'00',$i*2,2);
		if($debug&IO_DEBUG_ID) print "Setting Output $i to 'Off'\n";
	}
	//other values have no effect
}

function is_light_on(&$out_states,$i) {
	$os=substr($out_states,$i*2,2);
	if($os=='00') return 0; //00 is off anything else is on
	return 1;
}

function set_outputs(&$out_states) {
	global $out1,$out2;
	$o='';
	for($i=0;$i<64;$i++) $o.=(hexdec(substr($out_states,$i*2,2))?'1':'0');
	$r=wiringPiI2CWriteReg16 ($out1, PCA9555_OUTPORT0, bindec(substr($o,0,16))) ; //sends to OUTPORT0 and 1
	$r=wiringPiI2CWriteReg16 ($out2, PCA9555_OUTPORT0, bindec(substr($o,16,16))) ; //sends to OUTPORT0 and 1
}

function set_output_state(&$outputs,$i,$state) {
	global $debug;
	$outputs=substr_replace($outputs,$state,$i*2,2);
	if($debug&IO_DEBUG_ID) printf("Setting Hardware Output %s to '%s'\n",$i,($state=='00'?'Off':'On'));
}
