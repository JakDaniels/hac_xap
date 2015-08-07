<?php
// SPEC taken from http://www.enttec.com/docs/dmx_usb_pro_api_spec.pdf
// 0x01 and 0x02 are for firmware.
define("CMD_GET_PARAMETERS",0x03);	//config size LSB,MSB (max 508)
define("RX_GET_PARAMETERS",0x03);	//firmware version LSB,MSB, DMX BreakTime 0-127, DMX Mark after BreakTime 0-127, DMX Output rate 0-40, Config Data
define("CMD_SET_PARAMETERS",0x04);	//config size LSB,MSB (max 508), DMX BreakTime 0-127, DMX Mark after BreakTime 0-127, DMX Output rate 0-40, Config Data

define("RX_DMX_PACKET",0x05);	//first byte is status, data size: 1-513bytes
	define("RX_OK",0x00);
	define("DMX_DATA_START",0x00);
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

function dmx_connect() {
	global $debug,$dmx,$read_buffer;
	$r=trim(`ls -1 /dev/serial/by-id 2>/dev/null |grep "DMX"`);
	if($r=='') {
		logformat("No USB DMX Interface appears to be connected!\n");
		exit(1);
	}
	$t=explode("-",$r);
	if(isset($t[1])) logformat(sprintf("Found a %s\n",$t[1]));
	$interface="/dev/serial/by-id/".$r;
	`stty -F $interface 230400 raw -echo`;
	if($dmx=fopen($interface,'w+')) {
		stream_set_blocking($dmx, 0);
		stream_set_read_buffer($dmx , 2048);
		$data='';
		dmx_set_levels($data);
		dmx_set_dmx_receive_mode(SEND_ON_CHANGE_ONLY);
		sleep(1);
		while($b=fread($dmx,2048)); //empty the buffer
		$data="Hello World!";
		dmx_set_parameters(27,4,40,$data);
		if(!$c=dmx_request_parameters($read_buffer)) {
			dmx_close();
			return 0;
		}

		return $c;
	}
	return 0;
}

function dmx_request_parameters(&$read_buffer) {
	$data='';
	dmx_write(CMD_GET_PARAMETERS,0,$data);
	usleep(5000);
	while(dmx_read($read_buffer));
	if($x=dmx_get_next_packet($read_buffer)) {
		if(substr($x,0,2)==chr(DMX_START_BYTE).chr(RX_GET_PARAMETERS)) {
			$config['FW_VER']=sprintf("V%d.%d",ord(substr($x,5,1)),ord(substr($x,4,1)));
			$config['DMX_BR_TIME']=ord(substr($x,6,1));
			$config['DMX_MABR_TIME']=ord(substr($x,7,1));
			$config['DMX_OUTPUT_RATE']=ord(substr($x,8,1));
			$config['CONFIG_DATA']=substr($x,9,-2);
			return $config;
		}
	}
	return 0;
}

function dmx_close() {
	global $debug,$dmx;
	@fclose($dmx);
}

function dmx_read(&$read_buffer) {
	global $debug,$dmx;
	$r=fread($dmx,128);
	if($r!='') {
		if(strlen($read_buffer)<521-strlen($r)) $read_buffer.=$r;
		else $read_buffer=substr($read_buffer,strlen($r)).$r;
		return 1;
	}
	return 0;
}

function dmx_get_next_packet(&$read_buffer) {
	global $debug;
	$regexp=sprintf("/\x%02X(\x%02X[\x%02X-\x%02X]{1}.+?\x%02X)/",DMX_END_BYTE,DMX_START_BYTE,0x00,0x0B,DMX_END_BYTE);
	if(preg_match($regexp,$read_buffer,$m,PREG_OFFSET_CAPTURE)) {
		$packet=$m[1][0]; $offset=$m[1][1];
		$read_buffer=substr($read_buffer,0,$offset).substr($read_buffer,$offset+strlen($packet));
		if($debug&DMX_DEBUG_ID) logformat(sprintf("Received: %s\n",hex_display($packet)));
		return $packet;
	}
	return 0;
}

function dmx_write($cmd,$s,&$data) {
	global $debug,$dmx;
	$sLSB=$s%256;
	$sMSB=floor($s/256);
	$packet=chr(DMX_START_BYTE).chr($cmd).chr($sLSB).chr($sMSB).$data.chr(DMX_END_BYTE);
	if($debug&DMX_DEBUG_ID) logformat(sprintf("Sent: %s\n",hex_display($packet)));
	$w=fwrite($dmx,$packet);
	fflush($dmx);
	return $w;
}

function dmx_set_levels_U1(&$data) {
	global $debug,$dmx;
	if(strlen($data)>512) $data=substr($data,0,512);
	$data=chr(0).$data;
	$ds=strlen($data);
	return dmx_write(OUTPUT_ONLY_SEND_DMX_U1,$ds,$data);
}

function dmx_set_levels_U2(&$data) {
	global $debug,$dmx;
	if(strlen($data)>512) $data=substr($data,0,512);
	$data=chr(0).$data;
	$ds=strlen($data);
	return dmx_write(OUTPUT_ONLY_SEND_DMX_U2,$ds,$data);
}

function dmx_set_levels(&$data) {
	global $debug,$dmx;
	if(strlen($data)>512) $data=substr($data,0,512);
	$data=chr(0).$data;
	$ds=strlen($data);
	return dmx_write(OUTPUT_ONLY_SEND_DMX,$ds,$data);
}

function dmx_set_parameters($obt,$omabt,$opr,$cdata) {
	if($obt<9 or $obt>127) return 0;
	if($omabt<1 or $omabt>127) return 0;
	if($opr>40) return 0;
	if(strlen($cdata)>508) $cdata=substr($cdata,0,508);
	$cs=strlen($cdata);
	$csLSB=$cs%256;
	$csMSB=floor($cs/256);
	$data=chr($csLSB).chr($csMSB).chr($obt).chr($omabt).chr($opr).$cdata;
	dmx_write(CMD_SET_PARAMETERS,strlen($data),$data);
	return 1;
}

function dmx_set_dmx_receive_mode($change_only=SEND_ALWAYS) {
	global $debug,$dmx;
	$c=$change_only&1;
	return dmx_write(CMD_RX_DMX_ON_CHANGE,1,chr($c));
}

function dmx_get_dmx_rx_data($x) {
	if(substr($x,0,2)==chr(DMX_START_BYTE).chr(RX_DMX_PACKET)) {
		$status=substr($x,4,1);
		if($status==chr(RX_OK)) {
			$data_size=ord(substr($x,3,1))*256+ord(substr($x,2,1));
			$data=substr($x,6,-1);
			if(strlen($data)==$data_size-2)	return $data;
		}
	}
	return 0;
}

function dmx_get_dmx_change_data($x,&$dmx_input) {
	if(substr($x,0,2)==chr(DMX_START_BYTE).chr(RX_DMX_CHANGED_STATE)) {
		$data_size=ord(substr($x,3,1))*256+ord(substr($x,2,1));
		$data=substr($x,4,$data_size);

		$scbn=ord(substr($data,0,1));
		$cba=strrev(sprintf("%08b",ord(substr($data,1,1))));
		$cba.=strrev(sprintf("%08b",ord(substr($data,2,1))));
		$cba.=strrev(sprintf("%08b",ord(substr($data,3,1))));
		$cba.=strrev(sprintf("%08b",ord(substr($data,4,1))));
		$cba.=strrev(sprintf("%08b",ord(substr($data,5,1))));
		$cdba=substr($data,6);
		$cbi=0;
		for($i=0;$i<39;$i++) {
			if(substr($cba,$i,1)=='1') {
				$dmx_input=substr_replace($dmx_input,substr($cdba,$cbi,1),$scbn*8+$i-1,1);
				$cbi++;
			}
		}
		return 1;
	}
	return 0;
}
