#!/usr/bin/php
<?php

include("lib/defines.inc.php");
include("lib/functions.inc.php");
include("lib/xaplib.inc.php");

include("lib/io_functions.inc.php");
include("lib/cmd_functions.inc.php");

include("lib/io_xap_functions.inc.php");
include("lib/hac_io_defines.inc.php");
include("lib/hac_io_functions.inc.php");

include("lib/dmx_xap_functions.inc.php");
include("lib/hac_dmx_defines.inc.php");
include("lib/hac_dmx_functions.inc.php");
logformat("hac_cmd is starting....\n");
declare(ticks=10); //as of php 5.3 this must be in the main program, not just an include

$out_states=''; //these will be 64 2 digit hex items
$in_states='';
$in_programs='';
$in_levels='';
$inames=array(); 	//input names id=>name
$onames=array();	//output names id=>name
$itypes=array(); 	//input switch type
$otypes=array();	//output light type (state change delay setting)

if(!$r=io_read_names_file($inames_file,$inames,$itypes)) die("Could not read input names file $inames_file");
if(!$r=io_read_names_file($onames_file,$onames,$otypes)) die("Could not read output names file $onames_file");

if($debug&NAMES_DEBUG_ID) {
	logformat("Startup IO Inputs:\n");
	logformat(implode(", ",explode("\n",print_r($inames,1))));
	logformat("Startup IO Outputs:\n");
	logformat(implode(", ",explode("\n",print_r($onames,1))));
}

$dmx_inputs='';	//these will be 512 2 digit hex items
$dmx_outputs1='';
$dmx_outputs2='';

$dinames=array();		//input names id=>name
$donames1=array();	//output names id=>name universe 1
$donames2=array();	//output names id=>name universe 2

if(!$r=dmx_read_names_file($dinames_file,$dinames)) {
	logformat(sprintf("Could not read input names file %s\n",$dinames_file));
	exit(1);
}
if(!$r=dmx_read_names_file($donames1_file,$donames1)) {
	logformat(sprintf("Could not read output names file %s\n",$donames1_file));
	exit(1);
}
if(!$r=dmx_read_names_file($donames2_file,$donames2)) {
	logformat(sprintf("Could not read output names file %s\n",$donames2_file));
	exit(1);
}

if($debug&NAMES_DEBUG_ID) {
	logformat("Startup DMX Inputs:\n");
	logformat(implode(", ",explode("\n",print_r($dinames,1))));
	logformat("Startup DMX Outputs1:\n");
	logformat(implode(", ",explode("\n",print_r($donames1,1))));
	logformat("Startup DMX Outputs2:\n");
	logformat(implode(", ",explode("\n",print_r($donames2,1))));
}

if(!$io_shm_id=@shmop_open(IO_SHM_ID,'w',0,0)) {
	logformat("Could not open IO shared memory segment!\n");
	exit(1);
}
if(!$dmx_shm_id=@shmop_open(DMX_SHM_ID,'w',0,0)) {
	logformat("Could not open DMX shared memory segment!\n");
	exit(1);
}

//read the IO shared memory buffer
if(!io_read_shared_memory($io_shm_id,$out_states,$in_states,$in_programs,$in_levels)) {
	logformat("Could not read IO shared memory segment!\n");
	exit(1);
}

if($debug&IO_DEBUG_ID) {
	logformat("Startup State:\n");
	logformat(sprintf("Out States :%s\n",$out_states));
	logformat(sprintf("In States  :%s\n",$in_states));
	logformat(sprintf("In Programs:%s\n",$in_programs));
	logformat(sprintf("In Levels  :%s\n",$in_levels));
}

//read the DMX shared memory buffer
if(!dmx_read_shared_memory($dmx_shm_id,$dmx_inputs,$dmx_outputs1,$dmx_outputs2)) {
	logformat("Could not read DMX shared memory segment!\n");
	exit(1);
}

if($debug&DMX_DEBUG_ID) {
	logformat("Startup State:\n");
	logformat(sprintf("Input   :%s\n",$dmx_inputs));
	logformat(sprintf("Output 1:%s\n",$dmx_outputs1));
	logformat(sprintf("Outout 2:%s\n",$dmx_outputs2));
}

$io_last_info=time()-IOINFOSENDTIME-1;
$dmx_last_info=time()-DMXINFOSENDTIME-1;
$last_time=time()-XAPHEARTBEAT-1;
$last_tick_time=microtime(true);

$io_counter=0; //index to the endpoint for io info messages, we send one every 0.5 secs when IOINFOSENDTIME comes around
$dmx_counter=0;//index to the endpoint for dmx info messages, we send one every 0.5 secs when DMXINFOSENDTIME comes around

xap_connect(); //use this to set up the listener and register with the hub
$b='';
$os=''; $is=''; $ip=''; $il='';
$di=''; $do1=''; $do2='';


while(1) {
	if($must_exit) break;

	//send xAP heartbeat periodically
	$t=xap_check_send_heartbeat(array(XAPUID_IO_IN,XAPUID_IO_OUT,XAPUID_DMX_IN1,XAPUID_DMX_OUT1,XAPUID_DMX_OUT2),
															array(XAPSRC_IO_IN,XAPSRC_IO_OUT,XAPSRC_DMX_IN1,XAPSRC_DMX_OUT1,XAPSRC_DMX_OUT2));

	io_read_shared_memory($io_shm_id,$out_states,$in_states,$in_programs,$in_levels);
	dmx_read_shared_memory($dmx_shm_id,$dmx_inputs,$dmx_outputs1,$dmx_outputs2);

	if($xap=xap_listen($b)) {
		if($debug&MSG_DEBUG_ID) print_r($xap);
		$c=process_xap_msgs($xap);
	}

	if($os.$is.$ip.$il!=$out_states.$in_states.$in_programs.$in_levels) {
		if($debug&IO_DEBUG_ID) {
			logformat("Current IO State:\n");
			logformat(sprintf("Out States :%s\n",$out_states));
			logformat(sprintf("In States  :%s\n",$in_states));
			logformat(sprintf("In Programs:%s\n",$in_programs));
			logformat(sprintf("In Levels  :%s\n",$in_levels));
		}
		$os=$out_states; $is=$in_states; $ip=$in_programs; $il=$in_levels;
	}
	if($di.$do1.$do2!=$dmx_inputs.$dmx_outputs1.$dmx_outputs2) {
		if($debug&DMX_DEBUG_ID) {
			logformat("Current DMX State:\n");
			logformat(sprintf("Input   :%s\n",$dmx_inputs));
			logformat(sprintf("Output 1:%s\n",$dmx_outputs1));
			logformat(sprintf("Output 2:%s\n",$dmx_outputs2));
		}
		$di=$dmx_inputs; $do1=$dmx_outputs1; $do2=$dmx_outputs2;
	}

	if($t-$last_tick_time>0.5)	{
		//send info messages for switches and lights
		if($t-$io_last_info>IOINFOSENDTIME) {
			if($io_counter<IN_PINS) {
				send_io_input_level_info_message($io_counter,$in_states,$in_levels);
				send_io_input_program_info_message($io_counter,$in_states,$in_programs);
			}
			if($io_counter<OUT_PINS) send_io_output_binary_info_message($io_counter,$out_states);
			if($io_counter>=IN_PINS and $io_counter>=OUT_PINS) {
				$io_counter=0;
				$io_last_info=$t;
			}	else $io_counter++;
		}
		//send info messages for dmx inputs and outputs
		if($t-$dmx_last_info>DMXINFOSENDTIME) {
			if($dmx_counter<DMX_CH_IN) send_dmx_input_level_info_message($dmx_counter,$dmx_inputs);
			if($dmx_counter<DMX_CH_OUT1) send_dmx_output_level_info_message($dmx_counter,XAPSRC_DMX_OUT1,XAPUID_DMX_OUT1,$dmx_outputs1,$donames1);
			if($dmx_counter<DMX_CH_OUT2) send_dmx_output_level_info_message($dmx_counter,XAPSRC_DMX_OUT2,XAPUID_DMX_OUT2,$dmx_outputs2,$donames2);
			if($dmx_counter>=DMX_CH_IN and $dmx_counter>=DMX_CH_OUT1 and $dmx_counter>=DMX_CH_OUT2) {
				$dmx_counter=0;
				$dmx_last_info=$t;
			}	else $dmx_counter++;
		}
		$last_tick_time=$t;
	}

}
$t=xap_send_heartbeat_stop(	array(XAPUID_IO_IN,XAPUID_IO_OUT,XAPUID_DMX_IN1,XAPUID_DMX_OUT1,XAPUID_DMX_OUT2),
														array(XAPSRC_IO_IN,XAPSRC_IO_OUT,XAPSRC_DMX_IN1,XAPSRC_DMX_OUT1,XAPSRC_DMX_OUT2));

socket_close($xap_sock_in);
shmop_close($io_shm_id);
shmop_close($dmx_shm_id);
logformat("hac_cmd exiting cleanly.\n");