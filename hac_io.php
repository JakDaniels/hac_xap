#!/usr/bin/php
<?php

include("lib/defines.inc.php");
if(IO_LIBRARY=='A10Lime') include("lib/A10Lime_defines.inc.php");
if(IO_LIBRARY=='wiringPI') include("lib/wiringPI_defines.inc.php");

include("lib/functions.inc.php");
include("lib/xaplib.inc.php");

include("lib/io_defines.inc.php");
include("lib/io_functions.inc.php");
include("lib/io_xap_functions.inc.php");

include("lib/hac_io_defines.inc.php");
include("lib/hac_io_functions.inc.php");

GPIO_init();

# these are the multiplex outputs for the switches up/down lines
GPIO_pinMode(GPIO_PIN_UP, GPIO_OUTPUT);
GPIO_Output(GPIO_PIN_UP, GPIO_LOW);
GPIO_pinMode(GPIO_PIN_DN, GPIO_OUTPUT);
GPIO_Output(GPIO_PIN_DN, GPIO_LOW);


$in1=I2C_Setup(PCF8574_ADDR1);
$in2=I2C_Setup(PCF8574_ADDR2);
$in3=I2C_Setup(PCF8574_ADDR3);
$in4=I2C_Setup(PCF8574_ADDR4);
# set all pins as inputs on the input boards
$error=0;
if($i1=I2C_Write ($in1, 255)<0) $error++; //chip is bidirectional, set outputs to 1 to read as input
if($i2=I2C_Write ($in2, 255)<0) $error++; //chip is bidirectional, set outputs to 1 to read as input
if($i3=I2C_Write ($in3, 255)<0) $error++; //chip is bidirectional, set outputs to 1 to read as input
if($i4=I2C_Write ($in4, 255)<0) $error++; //chip is bidirectional, set outputs to 1 to read as input
if($i1) printf("Opto input board 1 with address: 0x%X is missing or not functioning!\n",PCF8574_ADDR1);
if($i2) printf("Opto input board 2 with address: 0x%X is missing or not functioning!\n",PCF8574_ADDR2);
if($i3) printf("Opto input board 3 with address: 0x%X is missing or not functioning!\n",PCF8574_ADDR3);
if($i4) printf("Opto input board 4 with address: 0x%X is missing or not functioning!\n",PCF8574_ADDR4);
if($error) exit(1);

$out1=I2C_Setup(PCA9555_ADDR1);
$out2=I2C_Setup(PCA9555_ADDR2);
# set all pins as outputs on the output boards and set all outputs high
$error=0;
if($o1=I2C_WriteReg16 ($out1, PCA9555_CONFPORT0, 0)<0) $error++; //sends to CONFPORT0 and 1
if($o2=I2C_WriteReg16 ($out2, PCA9555_CONFPORT0, 0)<0) $error++; //sends to CONFPORT0 and 1
if($o1) printf("PCA9555 based output board 1 with address: 0x%X is missing or not functioning!\n",PCA9555_ADDR1);
if($o2) printf("PCA9555 based output board 2 with address: 0x%X is missing or not functioning!\n",PCA9555_ADDR2);
if($error) exit(1);

$o=I2C_WriteReg16 ($out1, PCA9555_OUTPORT0, 65535) ; //sends to OUTPORT0 and 1
$o=I2C_WriteReg16 ($out2, PCA9555_OUTPORT0, 65535) ; //sends to OUTPORT0 and 1

$pc_up=array_fill(0,IN_PINS,0);
$pc_dn=array_fill(0,IN_PINS,0);

$out_states=''; //these will be 64 2 digit hex items
$in_states='';
$in_programs='';
$in_levels='';
$inames=array(); 	//input names id=>name
$onames=array();	//output names id=>name
$itypes=array(); 	//input switch type
$otypes=array();	//output light type (state change safety delay setting)
$otimes=array();	//last output state and time an output changed. Used to implement safety delays

if(isset($args['r']) or isset($args['reset'])) {
	print "Resetting to default state.\n";
	io_create_state_file($state_file,$out_states,$in_states,$in_programs,$in_levels);
}

if(!$r=io_read_state_file($state_file,$out_states,$in_states,$in_programs,$in_levels)) {
	if(!$r=io_create_state_file($state_file,$out_states,$in_states,$in_programs,$in_levels)) {
		die("Could not create persistence file $state_file");
	}
}

if(!$r=io_read_names_file($inames_file,$inames,$itypes)) {
	io_create_default_inames($inames,$itypes);
	if(!$r=io_create_names_file($inames_file,$inames,$itypes)) {
		die("Could not create default input names file $inames_file");
	}
}

if(!$r=io_read_names_file($onames_file,$onames,$otypes)) {
	io_create_default_onames($onames,$otypes);
	if(!$r=io_create_names_file($onames_file,$onames,$otypes)) {
		die("Could not create default output names file $onames_file");
	}
}

if($debug&NAMES_DEBUG_ID) {
	print "Startup Inputs:\n";
	print_r($inames);
	print "Startup Outputs:\n";
	print_r($onames);
}

if($debug&IO_DEBUG_ID) {
	print "Startup State:\n";
	printf("Out States :%s\n",$out_states);
	printf("In States  :%s\n",$in_states);
	printf("In Programs:%s\n",$in_programs);
	printf("In Levels  :%s\n",$in_levels);
}

$outputs=$out_states; //outputs is the real hardware output, out_states is our internal representation of it
set_outputs($outputs); //set the hardware outputs
$t=microtime(true); //and set the hardware output safety timers
for($i=0;$i<64;$i++) $otimes[$i+1]=sprintf("%d,%.02f",is_light_on($out_states,$i),$t);

if($shm_id=shmop_open(IO_SHM_ID,'c',0666, 64*2*4)) { //64 i/o, 2 hex digits each, 4 params
	io_write_shared_memory($shm_id,$out_states,$in_states,$in_programs,$in_levels);
} else die("Could not open shared memory segment!\n");

xap_connect();
$hold_count=floor(HOLDINTERVAL/POLLINTERVAL);

while(1) {
	if($must_exit) break;

	//send xAP heartbeat periodically
	//$t=xap_check_send_heartbeat();
	$t=microtime(true);

	$something_changed=0;

	//activate the up line, then check for any switches on
  GPIO_Output(GPIO_PIN_UP,GPIO_HIGH);
  $sw=sprintf('%08b',I2C_Read($in1)).sprintf('%08b',I2C_Read($in2)).sprintf('%08b',I2C_Read($in3)).sprintf('%08b',I2C_Read($in4));
  for($i=0;$i<IN_PINS;$i++) {
  	$switch_type=$itypes[$i+1];
  	if(substr($sw,$i,1)==='0') {
  		$pc_up[$i]++;
  		if($pc_up[$i]>$hold_count) { //UP is held on <---------------------------------------------------------------o

  			if($switch_type==SW_2P_M_CO) { //2 pole momentary centre off ------------------------------------
					if(is_switch_on($in_states,$i)) { 														//if switch is on then dec level
						$something_changed+=dec_level($i,$in_states,$in_programs,$in_levels);
					}
				}		//------------------------------------------------------------------------------------------

  		}
  	} else {
  		if($pc_up[$i]>0) {
  			if($pc_up[$i]<$hold_count) { //UP is pressed once <--------------------------------------------------------o

  				if($switch_type==SW_2P_M) { //2 pole momentary --------------------------------------------------
						if(is_switch_on($in_states,$i)) {														//if switch is ON then inc program
							$something_changed+=inc_program($i,$in_states,$in_programs,$in_levels);
						} else {																							//if switch is off then set program to 0
							$something_changed+=zero_program($i,$in_states,$in_programs,$in_levels);
						}
  				}		//------------------------------------------------------------------------------------------

					if($switch_type==SW_2P_M_CO) { //2 pole momentary centre off ------------------------------------
						if(is_switch_on($in_states,$i)) { 														//if switch is ON then turn OFF
							$something_changed+=switch_off($i,$in_states,$in_programs,$in_levels);
						} else { 																						//if switch is off then set program to 0
							$something_changed+=zero_program($i,$in_states,$in_programs,$in_levels);
						}
					}		//------------------------------------------------------------------------------------------

  			} else { //UP hold end <----------------------------------------------------------------------------------o
					// perhaps send an info message here
					if($switch_type==SW_2P_M_CO) { //2 pole momentary centre off ------------------------------------
						//do something
					}		//------------------------------------------------------------------------------------------
  			}
  		}
  		$pc_up[$i]=0;
  	}
  }
  GPIO_Output(GPIO_PIN_UP,GPIO_LOW);

	//activate the down line, then check for any switches on
  GPIO_Output(GPIO_PIN_DN,GPIO_HIGH);
  $sw=sprintf('%08b',I2C_Read($in1)).sprintf('%08b',I2C_Read($in2)).sprintf('%08b',I2C_Read($in3)).sprintf('%08b',I2C_Read($in4));
  for($i=0;$i<IN_PINS;$i++) {
  	if(substr($sw,$i,1)==='0') {
  		$switch_type=$itypes[$i+1];
  		$pc_dn[$i]++;
  		if($pc_dn[$i]>$hold_count) { //DOWN is held on <------------------------------------------------------------o

  			if($switch_type==SW_2P_M_CO) { //2 pole momentary centre off ------------------------------------
					if(is_switch_on($in_states,$i)) { 														//if switch is on then inc level
						$something_changed+=inc_level($i,$in_states,$in_programs,$in_levels);
					}
				}		//------------------------------------------------------------------------------------------

  		}
  	} else {
  		if($pc_dn[$i]>0) {
  			if($pc_dn[$i]<$hold_count) { //DOWN is pressed once <-----------------------------------------------------o

  				if($switch_type==SW_2P_M or $switch_type==SW_1P_M) { //1 and 2 pole momentary ------------------
						if(is_switch_on($in_states,$i)) {																//switch is on so switch off
							$something_changed+=switch_off($i,$in_states,$in_programs,$in_levels);
						}	else {																												//switch is off so switch on
							$something_changed+=switch_on($i,$in_states,$in_programs,$in_levels);
						}
  				}		//------------------------------------------------------------------------------------------

  				if($switch_type==SW_2P_M_CO) { //2 pole momentary centre off ------------------------------------
						if(is_switch_on($in_states,$i)) { 															//switch is on so inc program
							$something_changed+=inc_program($i,$in_states,$in_programs,$in_levels);
						} else { 																									//turn switch on at current program
							$something_changed+=switch_on($i,$in_states,$in_programs,$in_levels);
						}
					}		//------------------------------------------------------------------------------------------

  			} else { //DOWN hold end <--------------------------------------------------------------------------------o
					// perhaps send an info message here

					if($switch_type==SW_2P_M_CO) { //2 pole momentary centre off ------------------------------------
						//do something
					}		//------------------------------------------------------------------------------------------

  			}
  		}
  		$pc_dn[$i]=0;
  	}
  }
  GPIO_Output(GPIO_PIN_DN,GPIO_LOW);

  if($something_changed) io_write_shared_memory($shm_id,0,$in_states,$in_programs,$in_levels);

  //check the shared memory buffer for changes and send event messages
  $outputs_changed=0; $inputs_changed=0;
  $os=''; $is=''; $ip=''; $il='';
  io_read_shared_memory($shm_id,$os,$is,$ip,$il);
  if($os.$is.$ip.$il!=$out_states.$in_states.$in_programs.$in_levels) { //something changed
		//if($os!=$out_states) set_outputs($os); //set the hardware outputs
		for($i=0;$i<OUT_PINS;$i++) {
			if(substr($os,$i*2,2)!=substr($out_states,$i*2,2)) {
				send_io_output_binary_event_message($i,$os); //light
				$outputs_changed=1;
			}
		}
		for($i=0;$i<IN_PINS;$i++) {
			if(substr($is,$i*2,2)!=substr($in_states,$i*2,2) or substr($il,$i*2,2)!=substr($in_levels,$i*2,2)) {
				send_io_input_level_event_message($i,$is,$il); //switch state and level
				$inputs_changed=1;
			}
			if(substr($ip,$i*2,2)!=substr($in_programs,$i*2,2)) {
				send_io_input_program_event_message($i,$is,$ip); //switch program
				$inputs_changed=1;
			}
		}
	}

	//update internal state variables after any changes
	if($outputs_changed) $out_states=$os;
	if($inputs_changed) {
		$in_states=$is;
		$in_programs=$ip;
		$in_levels=$il;
	}

  //if any internal state changed, either by hardware input or via shm then save state file
  if($something_changed or $outputs_changed or $inputs_changed) {
  	io_write_state_file($state_file,$out_states,$in_states,$in_programs,$in_levels);
		if($debug&IO_DEBUG_ID) {
			print "Current State:\n";
			printf("Out States :%s\n",$out_states);
			printf("In States  :%s\n",$in_states);
			printf("In Programs:%s\n",$in_programs);
			printf("In Levels  :%s\n",$in_levels);
		}
  }

	// real outputs are subject to user definable safety delays, i.e. how quickly they can change state
	if($outputs!=$out_states) {
		$houtputs_changed=0;
		for($i=0;$i<64;$i++) {
			$lo=is_light_on($out_states,$i);
			$tstate=explode(',',$otimes[$i+1]); //array is id=>state,timestamp
			if($lo!=$tstate[0]) { //difference between internal and hardware (relay) output state
				if($t>0.25*$otypes[$i+1]+$tstate[1]) { //has the required delay passed?
					set_output_state($outputs,$i,substr($os,$i*2,2)); //toggle the real output
					$otimes[$i+1]=sprintf("%d,%.02f",1-$tstate[0],$t); //remember time of the state change
					$houtputs_changed++;
				}
			}
		}
		if($houtputs_changed) {
			set_outputs($outputs); //set the hardware outputs
			if($debug&IO_DEBUG_ID) {
				print "Current Hardware State:\n";
				printf("Out States :%s\n",$outputs);
			}
		}
	}

  if($must_exit) break;

	//now sleep until our next POLL period starts
  while((microtime(true)-$t)*1000<POLLINTERVAL) usleep(10000); //10ms sleep
}

io_write_state_file($state_file,$out_states,$in_states,$in_programs,$in_levels);
socket_close($xap_sock_in);
shmop_close($shm_id);
