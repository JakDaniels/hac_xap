<?php

function send_io_input_level_event_message($id,&$in_states,&$in_levels) {
	global $inames; //even though we are an input we use xAP output.state so we can have our state changed via xAP
	$endpoint_name='';
	$displaytext='';
	$state=substr($in_states,$id*2,2)=='FF'?'On':'Off';
	$level=hexdec(substr($in_levels,$id*2,2)).'/255';
	//send switch level and program as separate switch data (programUID = switchUID+64)
	$displaytext='';
	if(isset($inames[$id+1]) and $inames[$id+1]!='') {
		$endpoint_name=$inames[$id+1].'.Level';
		$displaytext=sprintf("displaytext=Status and Dimmer Level of Switch %s\n",$inames[$id+1]);
	}	$msg=sprintf("output.state\n{\nstate=%s\nlevel=%s\n%s}\n",$state,$level,$displaytext);
	xap_sendEventMsg($msg, '', xap_make_endpoint_source(XAPSRC_IO_IN,$id,$endpoint_name), xap_make_endpoint_uid(XAPUID_IO_IN,$id));
}

function send_io_input_level_info_message($id,&$in_states,&$in_levels) {
	global $inames;
	$endpoint_name='';
	$displaytext='';
	$state=substr($in_states,$id*2,2)=='FF'?'On':'Off';
	$level=hexdec(substr($in_levels,$id*2,2)).'/255';
	//send switch level and program as separate switch data (program = switchid+64)
	$displaytext='';
	if(isset($inames[$id+1]) and $inames[$id+1]!='') {
		$endpoint_name=$inames[$id+1].'.Level';
		$displaytext=sprintf("displaytext=Status and Dimmer Level of Switch %s\n",$inames[$id+1]);
	}	$msg=sprintf("output.state\n{\nstate=%s\nlevel=%s\n%s}\n",$state,$level,$displaytext);
	xap_sendInfoMsg($msg, '', xap_make_endpoint_source(XAPSRC_IO_IN,$id,$endpoint_name), xap_make_endpoint_uid(XAPUID_IO_IN,$id));
}

function send_io_input_program_event_message($id,&$in_states,&$in_programs) {
	global $inames;
	$endpoint_name='';
	$displaytext='';
	$state=substr($in_states,$id*2,2)=='FF'?'On':'Off';
	$program=hexdec(substr($in_programs,$id*2,2)).'/255';
	//send switch level and program as separate switch data (program = switchid+64)
	if(isset($inames[$id+1]) and $inames[$id+1]!='') {
		$endpoint_name=$inames[$id+1].'.Program';
		$displaytext=sprintf("displaytext=Program setting of Switch %s\n",$inames[$id+1]);
	}
	$msg=sprintf("output.state\n{\nstate=%s\nlevel=%s\n%s}\n",$state,$program,$displaytext);
	xap_sendEventMsg($msg, '', xap_make_endpoint_source(XAPSRC_IO_IN,$id+64,$endpoint_name), xap_make_endpoint_uid(XAPUID_IO_IN,$id+64));
}

function send_io_input_program_info_message($id,&$in_states,&$in_programs) {
	global $inames;
	$endpoint_name='';
	$displaytext='';
	$state=substr($in_states,$id*2,2)=='FF'?'On':'Off';
	$program=hexdec(substr($in_programs,$id*2,2)).'/255';
	//send switch level and program as separate switch data (program = switchid+64)
	if(isset($inames[$id+1]) and $inames[$id+1]!='') {
		$endpoint_name=$inames[$id+1].'.Program';
		$displaytext=sprintf("displaytext=Program setting of Switch %s\n",$inames[$id+1]);
	}
	$msg=sprintf("output.state\n{\nstate=%s\nlevel=%s\n%s}\n",$state,$program,$displaytext);
	xap_sendInfoMsg($msg, '', xap_make_endpoint_source(XAPSRC_IO_IN,$id+64,$endpoint_name), xap_make_endpoint_uid(XAPUID_IO_IN,$id+64));
}

function send_io_output_binary_event_message($id,&$out_states) {
	global $onames;
	$endpoint_name='';
	$displaytext='';
	$state=substr($out_states,$id*2,2)=='00'?'Off':'On';
	if(isset($onames[$id+1]) and $onames[$id+1]!='') {
		$endpoint_name=$onames[$id+1]; 
		$displaytext=sprintf("displaytext=Status of Lighting Fixture %s\n",$onames[$id+1]);
	}
	$msg=sprintf("output.state\n{\nstate=%s\n%s}\n",$state,$displaytext);
	xap_sendEventMsg($msg, '', xap_make_endpoint_source(XAPSRC_IO_OUT,$id,$endpoint_name), xap_make_endpoint_uid(XAPUID_IO_OUT,$id));
}

function send_io_output_binary_info_message($id,&$out_states) {
	global $onames;
	$endpoint_name='';
	$displaytext='';
	$state=substr($out_states,$id*2,2)=='FF'?'On':'Off';
	if(isset($onames[$id+1]) and $onames[$id+1]!='') {
		$endpoint_name=$onames[$id+1]; 
		$displaytext=sprintf("displaytext=Status of Lighting Fixture %s\n",$onames[$id+1]);
	}
	$msg=sprintf("output.state\n{\nstate=%s\n%s}\n",$state,$displaytext);
	xap_sendInfoMsg($msg, '', xap_make_endpoint_source(XAPSRC_IO_OUT,$id,$endpoint_name), xap_make_endpoint_uid(XAPUID_IO_OUT,$id));
}

function send_all_io_info_messages(&$out_states,&$in_states,&$in_programs,&$in_levels) {
	for($i=0;$i<IN_PINS;$i++) {
		send_io_input_level_info_message($i,$in_states,$in_levels);
		send_io_input_program_info_message($i,$in_states,$in_programs);
	}
	for($i=0;$i<OUT_PINS;$i++) {
		send_io_output_binary_info_message($i,$out_states);
	}
}
