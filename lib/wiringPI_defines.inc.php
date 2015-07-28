<?php

define('DEVICE','wiringPI');

define('GPIO_INPUT',INPUT);
define('GPIO_OUTPUT',OUTPUT);
define('GPIO_PER',PWM_OUTPUT);
define('GPIO_PULL_NONE',PUD_OFF);
define('GPIO_PULL_UP',PUD_UP);
define('GPIO_PULL_DOWN',PUD_DOWN);
define('GPIO_LOW',0);
define('GPIO_HIGH',1);

function GPIO_init() {
	$r=wiringPiSetup();
	if($r<0) die("Failed to open GPIO. Are you running as root?\n");
	return $r;
}
function GPIO_input($pin) {
	return digitalRead($pin);
}
function GPIO_pinmode($pin,$val) {
	return pinMode($pin,$val);
}
function GPIO_setcfg($pin,$val) {
	return pinMode($pin,$val);
}
function GPIO_getcfg($pin) { //unimplemented
	return 0;
}
function GPIO_output($pin,$val) {
	return digitalWrite($pin,$val);
}
function GPIO_pullup($pin,$pull) {
	return pullUpDnControl($pin,$pull);
}

function I2C_Read($fd) {
	return wiringPiI2CRead($fd);
}
function I2C_ReadReg8($fd,$reg) {
	return wiringPiI2CReadReg8($fd,$reg);
}
function I2C_ReadReg16($fd,$reg) {
	return wiringPiI2CReadReg16($fd,$reg);
}
function I2C_Write($fd,$data) {
	return wiringPiI2CWrite($fd,$data);
}
function I2C_WriteReg8($fd,$reg,$data) {
	return wiringPiI2CWriteReg8($fd,$reg,$data);
}
function I2C_WriteReg16($fd,$reg,$data) {
	return wiringPiI2CWriteReg16($fd,$reg,$data);
}
function I2C_SetupInterface($device,$devId) {
	$r=wiringPiI2CSetupInterface($device,$devId);
	if($r<0) die("Failed to open I2C. Are you running as root?\n");
	return $r;
}
function I2C_Setup($devId) {
	return wiringPiI2CSetup($devId);
}

function Serial_Open($device,$baud) {
	return SerialOpen($device,$baud);
}
function Serial_Close($fd) {
	SerialClose($fd);
}
function Serial_Flush($fd) {
	SerialFlush($fd);
}
function Serial_Putchar($fd,$c) {
	SerialPutchar($fd,$c);
}
function Serial_Puts($fd,$s) {
	SerialPuts($fd,$s);
}
function Serial_Printf($fd,$message) {
	SerialPrintf($fd,$message);
}
function Serial_DataAvail($fd) {
	return SerialDataAvail($fd);
}
function Serial_Getchar($fd) {
	return SerialGetchar($fd);
}

?>
