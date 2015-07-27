<?php

define ('XAPUID_DMX_IN1','FF20A200'); //our dmx input
define ('XAPUID_DMX_OUT1','FF20A300'); //our dmx output on universe 1
define ('XAPUID_DMX_OUT2','FF20A400'); //our dmx output on universe 2

define ('XAPSRC_DMX_IN1','Hengwm.HAC.DMXIn');
define ('XAPSRC_DMX_OUT1','Hengwm.HAC.DMXOut1');
define ('XAPSRC_DMX_OUT2','Hengwm.HAC.DMXOut2');

define ('DMXINFOSENDTIME',300); //how often do we send out info messages

# shared memory buffer
define ('DMX_SHM_ID',0xff21);

define('DMX_CH_IN',128); 	//128 channels of dmx input
define('DMX_CH_OUT1',128);//128 channels of dmx output on each universe	
define('DMX_CH_OUT2',128);

$dstate_file=dirname(__FILE__).'/../etc/hac_dmx_state.txt';
$dinames_file=dirname(__FILE__).'/../etc/hac_dmx_input_names.txt';
$donames1_file=dirname(__FILE__).'/../etc/hac_dmx_output1_names.txt';
$donames2_file=dirname(__FILE__).'/../etc/hac_dmx_output2_names.txt';
