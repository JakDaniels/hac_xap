<?php
//-----------------------------------------------------------------
function process_xap_msgs(&$xap) {
	global $debug;
	global $io_shm_id,$inames,$onames,$out_states,$in_states,$in_programs,$in_levels;
	global $dmx_shm_id,$dinames,$donames1,$donames2,$dmx_inputs,$dmx_outputs1,$dmx_outputs2;
	$io_changed=0; $dmx_changed=0;
	
	if(xap_check_header($xap,'xap-header')) {
		$msg=xap_get_message($xap);
		$cls=xap_get_class($xap);
		//messages targetted at our Lights (outputs) -----------------------------------------------------
		if(xap_check_target($xap,XAPSRC_IO_OUT)) { //just compare address first
			if(strcasecmp($cls,'xAPBSC.Query')===0) {
				if(strcasecmp($msg[0]['TYPE'],'request')===0) {
					for($i=0;$i<OUT_PINS;$i++) {
						$endpoint=sprintf("%s:%s",XAPSRC_IO_OUT,str_replace(' ','',$onames[$i+1]));
						if(xap_check_target($xap,$endpoint)) {
							send_io_output_binary_info_message($i,$out_states);
							break; //found the endpoint no need to check further
						}
					}
				}
			}		
			if(strcasecmp($cls,'xAPBSC.Cmd')===0) {
				for($i=0;$i<OUT_PINS;$i++) {
					$endpoint=sprintf("%s:%s",XAPSRC_IO_OUT,str_replace(' ','',$onames[$i+1]));
					if(xap_check_target($xap,$endpoint)) {
						if(preg_match("/^output\.state\.[0-9]+$/i",$msg[0]['TYPE'])) {
							$currentstate=get_light_state($out_states,$i);
							if(isset($msg[0]['DATA']['state'])) $newstate=$msg[0]['DATA']['state'];
							else $newstate=$currentstate; //keep existing value if state parameter not found
							if($currentstate===$newstate) {//no change so send info message only
								send_io_output_binary_info_message($i,$out_states);
							} else { //state change has been commanded so change it
								set_light_state($out_states,$i,$newstate);
								$io_changed++; //inc the flag that totals the number of changes, if>0 then we need to finally update the shm
							}
						}
						break; //found the endpoint no need to check further
					}
				}
			}
		}
		//messages targetted at our Switches (also outputs) ----------------------------------------------------
		if(xap_check_target($xap,XAPSRC_IO_IN)) { //just compare address first
			if(strcasecmp($cls,'xAPBSC.Query')===0) {
				if(strcasecmp($msg[0]['TYPE'],'request')===0) {
					for($i=0;$i<IN_PINS;$i++) {
						$endpoint=sprintf("%s:%s.Level",XAPSRC_IO_IN,str_replace(' ','',$inames[$i+1]));
						if(xap_check_target($xap,$endpoint)) {
							send_io_input_level_info_message($i,$in_states,$in_levels);
							break; //found the endpoint no need to check further
						}
						$endpoint=sprintf("%s:%s.Program",XAPSRC_IO_IN,str_replace(' ','',$inames[$i+1]));
						if(xap_check_target($xap,$endpoint)) {
							send_io_input_program_info_message($i,$in_states,$in_programs);
							break; //found the endpoint no need to check further
						}
					}
				}
			}		
			if(strcasecmp($cls,'xAPBSC.Cmd')===0) {
				for($i=0;$i<IN_PINS;$i++) {
					$endpoint=sprintf("%s:%s.Level",XAPSRC_IO_IN,str_replace(' ','',$inames[$i+1]));
					if(xap_check_target($xap,$endpoint)) {
						if(preg_match("/^output\.state\.[0-9]+$/i",$msg[0]['TYPE'])) {
							$currentstate=get_switch_state($in_states,$i);
							$currentlevel=get_switch_level($in_levels,$i);
							if(isset($msg[0]['DATA']['state'])) $newstate=$msg[0]['DATA']['state'];
							else $newstate=$currentstate; //keep existing value if state parameter not found
							if(isset($msg[0]['DATA']['level'])) $newlevel=$msg[0]['DATA']['level'];
							else $newlevel=$currentlevel; //keep existing value if level parameter not found
							
							if($currentstate===$newstate and $currentlevel===$newlevel) {//no change so send info message only
								send_io_input_level_info_message($i,$in_states,$in_levels);
							} else { //state change has been commanded so change it
								set_switch_state($in_states,$i,$newstate);
								set_switch_level($in_levels,$i,$newlevel);
								$io_changed++; //inc the flag that totals the number of changes, if>0 then we need to finally update the shm
							}
						}
						break; //found the endpoint no need to check further
					}
					$endpoint=sprintf("%s:%s.Program",XAPSRC_IO_IN,str_replace(' ','',$inames[$i+1]));
					if(xap_check_target($xap,$endpoint)) {
						if(preg_match("/^output\.state\.[0-9]+$/i",$msg[0]['TYPE'])) {
							$currentprogram=get_switch_program($in_programs,$i);
							if(isset($msg[0]['DATA']['level'])) $newprogram=$msg[0]['DATA']['level'];
							else $newprogram=$currentprogram; //keep existing value if level parameter not found
							
							if($currentprogram===$newprogram) {//no change so send info message only
								send_io_input_program_info_message($i,$in_states,$in_programs);
							} else { //program change has been commanded so change it
								set_switch_program($in_programs,$i,$newprogram);
								$io_changed++; //inc the flag that totals the number of changes, if>0 then we need to finally update the shm
							}
						}
						break; //found the endpoint no need to check further
					}					
				}
			}
		}
		//--------------------------------------------------------------------------------------------------
		//messages targetted at our DMX outputs -----------------------------------------------------
		if(xap_check_target($xap,XAPSRC_DMX_OUT1) or xap_check_target($xap,XAPSRC_DMX_OUT2)) { //just compare address first
			if(strcasecmp($cls,'xAPBSC.Query')===0) {
				if(strcasecmp($msg[0]['TYPE'],'request')===0) {
					$ef=0;
					for($i=0;$i<DMX_CH_OUT1;$i++) {
						$endpoint=sprintf("%s:%s",XAPSRC_DMX_OUT1,str_replace(' ','',$donames1[$i+1]));
						if(xap_check_target($xap,$endpoint)) {
							send_dmx_output_level_info_message($i,XAPSRC_DMX_OUT1,XAPUID_DMX_OUT1,$dmx_outputs1,$donames1);
							$ef=1;
							break;  //found the endpoint no need to check further
						}
					}
					if(!$ef) {
						for($i=0;$i<DMX_CH_OUT2;$i++) {
							$endpoint=sprintf("%s:%s",XAPSRC_DMX_OUT2,str_replace(' ','',$donames2[$i+1]));
							if(xap_check_target($xap,$endpoint)) {
								send_dmx_output_level_info_message($i,XAPSRC_DMX_OUT2,XAPUID_DMX_OUT2,$dmx_outputs2,$donames2);
								break; //found the endpoint no need to check further
							}
						}
					}
				}
			}		
			if(strcasecmp($cls,'xAPBSC.Cmd')===0) {
				$ef=0;
				for($i=0;$i<DMX_CH_OUT1;$i++) {
					$endpoint=sprintf("%s:%s",XAPSRC_DMX_OUT1,str_replace(' ','',$donames1[$i+1]));
					if(xap_check_target($xap,$endpoint)) {
						if(preg_match("/^output\.state\.[0-9]+$/i",$msg[0]['TYPE'])) {
							$currentlevel=get_dmx_level($dmx_outputs1,$i);
							if(isset($msg[0]['DATA']['level'])) $newlevel=$msg[0]['DATA']['level'];
							else $newlevel=$currentlevel; //keep existing value if level parameter not found
							if($currentlevel===$newlevel) {//no change so send info message only
								send_dmx_output_level_info_message($i,XAPSRC_DMX_OUT1,XAPUID_DMX_OUT1,$dmx_outputs1,$donames1);
							} else { //state change has been commanded so change it
								set_dmx_level($dmx_outputs1,$i,$newlevel);
								$dmx_changed++; //inc the flag that totals the number of changes, if>0 then we need to finally update the shm
							}
						}
						$ef=1;
						break; //found the endpoint no need to check further
					}
				}
				if(!$ef) {
					for($i=0;$i<DMX_CH_OUT2;$i++) {
						$endpoint=sprintf("%s:%s",XAPSRC_DMX_OUT2,str_replace(' ','',$donames2[$i+1]));
						if(xap_check_target($xap,$endpoint)) {
							if(preg_match("/^output\.state\.[0-9]+$/i",$msg[0]['TYPE'])) {
								$currentlevel=get_dmx_level($dmx_outputs2,$i);
								if(isset($msg[0]['DATA']['level'])) $newlevel=$msg[0]['DATA']['level'];
								else $newlevel=$currentlevel; //keep existing value if level parameter not found
								if($currentlevel===$newlevel) {//no change so send info message only
									send_dmx_output_level_info_message($i,XAPSRC_DMX_OUT2,XAPUID_DMX_OUT2,$dmx_outputs2,$donames2);
								} else { //state change has been commanded so change it
									set_dmx_level($dmx_outputs2,$i,$newlevel);
									$dmx_changed++; //inc the flag that totals the number of changes, if>0 then we need to finally update the shm
								}
							}
							break; //found the endpoint no need to check further
						}
					}
				}
			}
		}		
		
		
		
	}
	if($io_changed) { //rather than update the shm on every endpoint change, we do it after *any* changes as one block
		io_write_shared_memory($io_shm_id,$out_states,$in_states,$in_programs,$in_levels);
	}
	if($dmx_changed) { //rather than update the shm on every endpoint change, we do it after *any* changes as one block
		dmx_write_shared_memory($dmx_shm_id,0,$dmx_outputs1,$dmx_outputs2);
	}
	return ($io_changed>0)+($dmx_changed>0)*2;
}

