<?php


function send_dmx_output_level_event_message($id,$src,$uid,&$out_states,&$onames) {
	$endpoint_name='';
	$displaytext='';
	$state=substr($out_states,$id*2,2)=='00'?'Off':'On';
	$level=hexdec(substr($out_states,$id*2,2)).'/255';
	$displaytext='';
	if(isset($onames[$id+1]) and $onames[$id+1]!='') {
		$endpoint_name=$onames[$id+1];
		$displaytext=sprintf("displaytext=Status and Dimmer Level of DMX Channel %s\n",$onames[$id+1]);
	}	$msg=sprintf("output.state\n{\nstate=%s\nlevel=%s\n%s}\n",$state,$level,$displaytext);
	xap_sendEventMsg($msg, '', xap_make_endpoint_source($src,$id,$endpoint_name), xap_make_endpoint_uid($uid,$id));
}

function send_dmx_output_level_info_message($id,$src,$uid,&$out_states,&$onames) {
	$endpoint_name='';
	$displaytext='';
	$state=substr($out_states,$id*2,2)=='00'?'Off':'On';
	$level=hexdec(substr($out_states,$id*2,2)).'/255';
	$displaytext='';
	if(isset($onames[$id+1]) and $onames[$id+1]!='') {
		$endpoint_name=$onames[$id+1];
		$displaytext=sprintf("displaytext=Status and Dimmer Level of DMX Channel %s, %s\n",$id+1,$onames[$id+1]);
	}	$msg=sprintf("output.state\n{\nstate=%s\nlevel=%s\n%s}\n",$state,$level,$displaytext);
	xap_sendInfoMsg($msg, '', xap_make_endpoint_source($src,$id,$endpoint_name), xap_make_endpoint_uid($uid,$id));
}

function send_dmx_input_level_event_message($id,&$in_states) {
	global $dinames;
	$endpoint_name='';
	$displaytext='';
	$state=substr($in_states,$id*2,2)=='00'?'Off':'On';
	$level=hexdec(substr($in_states,$id*2,2)).'/255';
	$displaytext='';
	if(isset($dinames[$id+1]) and $dinames[$id+1]!='') {
		$endpoint_name=$dinames[$id+1].'.Level';
		$displaytext=sprintf("displaytext=Status and Dimmer Level of DMX Channel %s, %s\n",$id+1,$dinames[$id+1]);
	}	$msg=sprintf("input.state\n{\nstate=%s\nlevel=%s\n%s}\n",$state,$level,$displaytext);
	xap_sendEventMsg($msg, '', xap_make_endpoint_source(XAPSRC_DMX_IN1,$id,$endpoint_name), xap_make_endpoint_uid(XAPUID_DMX_IN1,$id));
}

function send_dmx_input_level_info_message($id,&$in_states) {
	global $dinames;
	$endpoint_name='';
	$displaytext='';
	$state=substr($in_states,$id*2,2)=='00'?'Off':'On';
	$level=hexdec(substr($in_states,$id*2,2)).'/255';
	$displaytext='';
	if(isset($dinames[$id+1]) and $dinames[$id+1]!='') {
		$endpoint_name=$dinames[$id+1].'.Level';
		$displaytext=sprintf("displaytext=Status and Dimmer Level of DMX Channel %s, %s\n",$id+1,$dinames[$id+1]);
	}	$msg=sprintf("input.state\n{\nstate=%s\nlevel=%s\n%s}\n",$state,$level,$displaytext);
	xap_sendInfoMsg($msg, '', xap_make_endpoint_source(XAPSRC_DMX_IN1,$id,$endpoint_name), xap_make_endpoint_uid(XAPUID_DMX_IN1,$id));
}

function send_all_dmx_info_messages(&$dmx_inputs,&$dmx_outputs1,&$dmx_outputs2) {
	global $donames1,$donames2;
	for($i=0;$i<DMX_CH_IN;$i++) send_dmx_input_level_info_message($i,$dmx_inputs);
	for($i=0;$i<DMX_CH_OUT1;$i++) send_dmx_output_level_info_message($i,XAPSRC_DMX_OUT1,XAPUID_DMX_OUT1,$dmx_outputs1,$donames1);
	for($i=0;$i<DMX_CH_OUT2;$i++) send_dmx_output_level_info_message($i,XAPSRC_DMX_OUT2,XAPUID_DMX_OUT2,$dmx_outputs2,$donames2);
}