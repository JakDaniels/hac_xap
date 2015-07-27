#!/usr/bin/php
<?php

include("lib/php_serial.class.php");


$s=new phpSerial();
$s->deviceSet("/dev/ttyUSB0");
$s->confBaudRate(9600); 
$s->deviceOpen();
$s->sendMessage("Hello!");

$r=$s->readPort();
print $r;
$s->deviceClose();
