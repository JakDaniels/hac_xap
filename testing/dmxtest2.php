#!/usr/bin/php
<?php
include("lib/functions.inc.php");
define("CMD_GET_PARAMETERS",0x03);	//config size LSB,MSB (max 508)
define("RX_GET_PARAMETERS",0x03);	//firmware version LSB,MSB, DMX BreakTime 0-127, DMX Mark after BreakTime 0-127, DMX Output rate 0-40, Config Data
define("CMD_SET_PARAMETERS",0x04);	//config size LSB,MSB (max 508), DMX BreakTime 0-127, DMX Mark after BreakTime 0-127, DMX Output rate 0-40, Config Data

define("RX_DMX_PACKET",0x05);	//first byte is status, data size: 1-513bytes
	define("RX_OK",0x00);
	define("ERROR_QUEUE_OVERFLOW",0x01); //bit 0
	define("ERROR_RECEIVE_OVERRUN",0x02); //bit 1

define("OUTPUT_ONLY_SEND_DMX",0x06);			//data size: 25-513bytes
define("SEND_RDM_PACKET_REQUEST",0x07);		//data size: 1-513bytes

define("OUTPUT_ONLY_SEND_DMX_U1",0x64);		//data size: 25-513bytes (Universe 1, out 1+2 DMXKing only)
define("OUTPUT_ONLY_SEND_DMX_U2",0x65);		//data size: 25-513bytes (Universe 2, out 3+4 DMXKing only)

define("CMD_RX_DMX_ON_CHANGE",0x08);
	define("SEND_ALWAYS",0);
	define("SEND_ON_CHANGE_ONLY",1);
define("RX_DMX_CHANGED_STATE",0x09);

define("CMD_GET_SERIAL_NUMBER",0x0A);
define("CMD_SEND_RDM_DISCOVERY",0x0B);

define("DMX_START_BYTE",0x7E);
define("DMX_END_BYTE",0xE7);

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

exec('stty -F /dev/ttyUSB0 115200 raw -echo');
if($dmx=fopen('/dev/ttyUSB0','w+')) {
	dmx_set_levels('');

	$d1=str_pad('',128,chr(0));
	$d2=str_pad('',128,chr(0));
	dmx_set_levels_U1($d1);
	dmx_set_levels_U2($d2);

	$t=microtime(true);

	for($i=0;$i<=255;$i++) {
		$d1=str_pad('',128,chr($i));
		$d2=str_pad('',128,chr(255-$i));
		dmx_set_levels_U1($d1);
		dmx_set_levels_U2($d2);
		if($must_exit) break;
	}

	fclose($dmx);
}
print microtime(true)-$t. "seconds\n";
	
	
	
function dmx_write($cmd,$s,$data) {
	global $debug,$dmx;
	$sLSB=$s%256;
	$sMSB=floor($s/256);
	$packet=chr(DMX_START_BYTE).chr($cmd).chr($sLSB).chr($sMSB).$data.chr(DMX_END_BYTE);
	if($debug) printf("Sent: %s\n",hex_display($packet));
	return fwrite($dmx,$packet);
}

function dmx_set_levels_U1($data) {
	global $debug,$dmx;
	if(strlen($data)>512) $data=substr($data,0,512);
	$data=chr(0).$data;
	$ds=strlen($data);
	return dmx_write(OUTPUT_ONLY_SEND_DMX_U1,$ds,$data);
}

function dmx_set_levels_U2($data) {
	global $debug,$dmx;
	if(strlen($data)>512) $data=substr($data,0,512);
	$data=chr(0).$data;
	$ds=strlen($data);
	return dmx_write(OUTPUT_ONLY_SEND_DMX_U2,$ds,$data);
}

function dmx_set_levels($data) {
	global $debug,$dmx;
	if(strlen($data)>512) $data=substr($data,0,512);
	$data=chr(0).$data;
	$ds=strlen($data);
	return dmx_write(OUTPUT_ONLY_SEND_DMX,$ds,$data);
}

function hex_display($s) {
	$o='';
	for($i=0;$i<strlen($s);$i++) $o.=sprintf("%02X ",ord(substr($s,$i,1)));
	return $o;
}