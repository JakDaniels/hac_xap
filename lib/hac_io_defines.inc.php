<?php

define ('XAPUID_IO_IN','FF20A000'); //our inputs i.e. switches
define ('XAPUID_IO_OUT','FF20A100'); //our outputs i.e. lights
define ('XAPSRC_IO_IN','Hengwm.HAC.Switches');
define ('XAPSRC_IO_OUT','Hengwm.HAC.Lights');

define ('IOINFOSENDTIME',300); //how often do we send out info messages

# shared memory buffer
define ('IO_SHM_ID',0xff20);

define('IN_PINS',32); //switches
define('OUT_PINS',32); //lights

$state_file=dirname(__FILE__).'/../etc/hac_io_state.txt';
$inames_file=dirname(__FILE__).'/../etc/hac_io_input_names.txt';
$onames_file=dirname(__FILE__).'/../etc/hac_io_output_names.txt';

