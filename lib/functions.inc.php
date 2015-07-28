<?php

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

function signal_handler($signal) {
	global $must_exit;
	switch($signal) {
		case SIGTERM:
			$must_exit='SIGTERM';
			break;
		case SIGKILL:
			$must_exit='SIGKILL';
			break;
		case SIGINT:
			$must_exit='SIGINT';
			break;
	}
	print $must_exit."\n";
}

function parse_args(&$argc,&$argv) {
	$argv[]="";
	$argv[]="";
	$args=array();
	//build a hashed array of all the arguments
	$i=1; $ov=0;
	while ($i<$argc) {
		if (substr($argv[$i],0,2)=="--") $a=substr($argv[$i++],2);
		elseif (substr($argv[$i],0,1)=="-") $a=substr($argv[$i++],1);
		else $a=$ov++;
		if (strpos($a,"=") >0) {
			$tmp=explode("=",$a);
			$args[$tmp[0]]=$tmp[1];
		} else {
			if (substr($argv[$i],0,1)=="-" or $i==$argc) $v=1;
			else $v=$argv[$i++];
			$args[$a]=$v;
		}
	}
	return $args;
}

function hex_display($s) {
	$o='';
	for($i=0;$i<strlen($s);$i++) $o.=sprintf("%02X ",ord(substr($s,$i,1)));
	return $o;
}

function bcd_to_chr($data) {
	$r='';
	for($i=0;$i<128;$i++) $r.=chr(hexdec(substr($data,$i*2,2)));
	return $r;
}

function chr_to_bcd($s) {
	$data='';
	for($i=0;$i<128;$i++) $data.=sprintf("%02X",ord(substr($s,$i,1)));
	return $data;
}
