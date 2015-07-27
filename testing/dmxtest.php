#!/usr/bin/php
<?php

include("lib/dmx_functions.inc.php");
include("lib/php_serial.class.php");
include("lib/functions.inc.php");

define ('DMX_DEV','/dev/ttyUSB0');
define ('SERIAL_BAUD',115200);

date_default_timezone_set('Europe/London');
ob_implicit_flush ();
set_time_limit (0);
// signal handling
declare(ticks=1); $must_exit=0;
pcntl_signal(SIGTERM, "signal_handler");
pcntl_signal(SIGINT, "signal_handler");

$argc=$_SERVER["argc"];
$argv=$_SERVER["argv"]; //$argv is an array
if($argc==0) error(usage());
$args=parse_args($argc,$argv);
if(isset($args['d'])) $debug=$args['d'];
elseif(isset($args['debug'])) $debug=$args['debug'];
else $debug=0;

$dmx=new phpSerial();
$dmx->deviceSet(DMX_DEV);
$dmx->confBaudRate(SERIAL_BAUD); 
if(!$dmx->deviceOpen()) die("Could not open DMX Interface on ".DMX_DEV."\n");
$f=0;
if($sn=dmx_request_serial_number()) {
	printf("Found DMX Interface with Serial Number: %s\n",$sn);
	if($fw=dmx_request_parameters()) {
		printf("Firmware Version: %s\n",$fw['FW_VER']);
		printf("DMX Output Break Time: %s x 10.67 = %.02f us\n",$fw['DMX_BR_TIME'],$fw['DMX_BR_TIME']*10.67);
		printf("DMX Mark After Break Time: %s x 10.67 = %.02f us\n",$fw['DMX_MABR_TIME'],$fw['DMX_MABR_TIME']*10.67);
		printf("DMX Output Rate: %s packets/sec\n",$fw['DMX_OUTPUT_RATE']);
	}
	$f=1;
}
if(!$f) die("Could not find a DMX interface!\n");

$d='';
dmx_set_levels($d);
$d1=str_pad('',512,chr(0));
$d2=str_pad('',512,chr(0));
dmx_set_levels_U1($d1);
dmx_set_levels_U2($d2);



for($i=0;$i<=255;$i++) {
	$d1=str_pad('',512,chr($i));
	$d2=str_pad('',512,chr(255-$i));
	dmx_set_levels_U1($d1);
	dmx_set_levels_U2($d2);
	if($must_exit) break;
}



$dmx->deviceClose();