#!/usr/bin/php
<?php
include("lib/dmx_functions.inc.php");
include("lib/functions.inc.php");

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

$read_buffer=str_pad('',519,chr(0)).chr(DMX_END_BYTE);

if(!$fw=dmx_connect()) {
	printf("Buffer: %s\n",hex_display($read_buffer));
	die("Could not talk to DMX Interface!\n");
}
printf("Firmware Version: %s\n",$fw['FW_VER']);
printf("DMX Output Break Time: %s x 10.67 = %.02f us\n",$fw['DMX_BR_TIME'],$fw['DMX_BR_TIME']*10.67);
printf("DMX Mark After Break Time: %s x 10.67 = %.02f us\n",$fw['DMX_MABR_TIME'],$fw['DMX_MABR_TIME']*10.67);
printf("DMX Output Rate: %s packets/sec\n",$fw['DMX_OUTPUT_RATE']);
printf("DMX Config Data: %s\n",$fw['CONFIG_DATA']);

$dmx_input=str_pad('',128,chr(0));

$data=str_pad('',128,chr(0));
dmx_set_levels_U1($data);
dmx_set_levels_U2($data);

//dmx_set_dmx_receive_mode(SEND_ALWAYS);
dmx_set_dmx_receive_mode(SEND_ON_CHANGE_ONLY);
while(1) {
	if($must_exit) break;
	
	if(dmx_read($read_buffer)) { //got some input from the interface
		while($packet=dmx_get_next_packet($read_buffer)) {
			if($p=dmx_get_dmx_rx_data($packet)) {
				if($debug) printf("DMX Received: %s\n",hex_display($p));
			}
			if($p=dmx_get_dmx_change_data($packet,$dmx_input)) {
				if($debug) printf("DMX Received: %s\n",hex_display($dmx_input));
			}
		}
	} else usleep(5000);
	
}

dmx_set_dmx_receive_mode(SEND_ON_CHANGE_ONLY);
dmx_close();
