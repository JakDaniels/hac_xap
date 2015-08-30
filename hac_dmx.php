#!/usr/bin/php
<?php

include("lib/defines.inc.php");
include("lib/functions.inc.php");
include("lib/xaplib.inc.php");
include("lib/dmxlib.inc.php");
include("lib/dmx_xap_functions.inc.php");

include("lib/hac_dmx_defines.inc.php");
include("lib/hac_dmx_functions.inc.php");
logformat("hac_dmx is starting....\n");
declare(ticks=10); //as of php 5.3 this must be in the main program, not just an include

$dmx_inputs='';	//these will be 512 2 digit hex items
$dmx_outputs1='';
$dmx_outputs2='';

$dinames=array();		//input names id=>name
$donames1=array();	//output names id=>name universe 1
$donames2=array();	//output names id=>name universe 2

if(isset($args['r']) or isset($args['reset'])) {
	print "Resetting to default state.\n";
	dmx_create_state_file($dstate_file,$dmx_inputs,$dmx_outputs1,$dmx_outputs2);
}

if(!$r=dmx_read_state_file($dstate_file,$dmx_inputs,$dmx_outputs1,$dmx_outputs2)) {
	if(!$r=dmx_create_state_file($dstate_file,$dmx_inputs,$dmx_outputs1,$dmx_outputs2)) {
		logformat(sprintf("Could not create persistence file %s\n",$dstate_file));
		exit(1);
	}
}

if(!$r=dmx_read_names_file($dinames_file,$dinames)) {
	dmx_create_default_inames($dinames);
	if(!$r=dmx_create_names_file($dinames_file,$dinames)) {
		logformat(sprintf("Could not create default input names file %s\n",$dinames_file));
		exit(1);
	}
}

if(!$r=dmx_read_names_file($donames1_file,$donames1)) {
	dmx_create_default_onames($donames1);
	if(!$r=dmx_create_names_file($donames1_file,$donames1)) {
		logformat(sprintf("Could not create default output names file %s\n",$donames1_file));
		exit(1);
	}
}

if(!$r=dmx_read_names_file($donames2_file,$donames2)) {
	dmx_create_default_onames($donames2);
	if(!$r=dmx_create_names_file($donames2_file,$donames2)) {
		logformat(sprintf("Could not create default output names file %s\n",$donames2_file));
		exit(1);
	}
}

if($debug&NAMES_DEBUG_ID) {
	logformat("Startup DMX Inputs:\n");
	logformat(implode(", ",explode("\n",print_r($dinames,1))));
	logformat("Startup DMX Outputs1:\n");
	logformat(implode(", ",explode("\n",print_r($donames1,1))));
	logformat("Startup DMX Outputs2:\n");
	logformat(implode(", ",explode("\n",print_r($donames2,1))));
}

if($debug&DMX_DEBUG_ID) {
	logformat("Startup State:\n");
	logformat(sprintf("Input   :%s\n",$dmx_inputs));
	logformat(sprintf("Output 1:%s\n",$dmx_outputs1));
	logformat(sprintf("Outout 2:%s\n",$dmx_outputs2));
}


if($shm_id=shmop_open(DMX_SHM_ID,'c',0644, 128*2*3)) { //128 channels, 2 hex digits each, 3 params
	dmx_write_shared_memory($shm_id,$dmx_inputs,$dmx_outputs1,$dmx_outputs2);
} else {
	logformat("Could not open shared memory segment!\n");
	exit(1);
}

xap_connect();

$read_buffer=str_pad('',519,chr(0)).chr(DMX_END_BYTE);

if(!$fw=dmx_connect()) {
	logformat("Could not talk to DMX Interface!\n");
	exit(1);
}
logformat(sprintf("Firmware Version: %s\n",$fw['FW_VER']));
logformat(sprintf("DMX Output Break Time: %s x 10.67 = %.02f us\n",$fw['DMX_BR_TIME'],$fw['DMX_BR_TIME']*10.67));
logformat(sprintf("DMX Mark After Break Time: %s x 10.67 = %.02f us\n",$fw['DMX_MABR_TIME'],$fw['DMX_MABR_TIME']*10.67));
logformat(sprintf("DMX Output Rate: %s packets/sec\n",$fw['DMX_OUTPUT_RATE']));
logformat(sprintf("DMX Config Data: %s\n",$fw['CONFIG_DATA']));

$dmx_input=bcd_to_chr($dmx_inputs);
$dmx_output1=bcd_to_chr($dmx_outputs1);
$dmx_output2=bcd_to_chr($dmx_outputs2);
dmx_set_levels_U1($dmx_output1);
dmx_set_levels_U2($dmx_output2);

dmx_set_dmx_receive_mode(SEND_ON_CHANGE_ONLY);
while(1) {
	if($must_exit) break;

	if(dmx_read($read_buffer)) { //got some input from the interface
		while($packet=dmx_get_next_packet($read_buffer)) {
			$di='';
			if($p=dmx_get_dmx_change_data($packet,$dmx_input)) {
				if($debug&DMX_DEBUG_ID) logformat(sprintf("DMX Received: %s\n",hex_display($dmx_input)));
				$di=chr_to_bcd($dmx_input);
				dmx_write_shared_memory($shm_id,$di,0,0);
				//send xap event(s)
				for($i=0;$i<128;$i++) {
					if(substr($di,$i*2,2)!=substr($dmx_inputs,$i*2,2)) {
						send_dmx_input_level_event_message($i,$di);
					}
				}
				$dmx_inputs=$di;
			}
		}
	} else {
		$outs1_changed=0; $outs2_changed=0;
		$do1=''; $do2='';
		dmx_read_shared_memory($shm_id,$di,$do1,$do2);
		if($do1.$do2!=$dmx_outputs1.$dmx_outputs2) { //something changed in shm
			for($i=0;$i<128;$i++) {
				if(substr($do1,$i*2,2)!=substr($dmx_outputs1,$i*2,2)) {
					//send xap dmx level change message for universe 1
					send_dmx_output_level_event_message($i,XAPSRC_DMX_OUT1,XAPUID_DMX_OUT1,$do1,$donames1);
					$outs1_changed=1;
				}
				if(substr($do2,$i*2,2)!=substr($dmx_outputs2,$i*2,2)) {
					//send xap dmx level change message for universe 2
					send_dmx_output_level_event_message($i,XAPSRC_DMX_OUT2,XAPUID_DMX_OUT2,$do2,$donames2);
					$outs2_changed=1;
				}
			}
		}
		if($outs1_changed) {
			$dmx_outputs1=$do1;
			dmx_set_levels_U1(bcd_to_chr($dmx_outputs1));
		}
		if($outs2_changed) {
			$dmx_outputs2=$do2;
			dmx_set_levels_U2(bcd_to_chr($dmx_outputs2));
		}
		usleep(5000);
	}
}

dmx_set_dmx_receive_mode(SEND_ON_CHANGE_ONLY);
dmx_close();
dmx_write_state_file($dstate_file,$dmx_inputs,$dmx_outputs1,$dmx_outputs2);
socket_close($xap_sock_in);
shmop_close($shm_id);
logformat("hac_dmx exiting cleanly.\n");
