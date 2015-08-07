<?php

define ('XAPUID','FF10A200');
define ('XAPSOURCE','Hengwm.HAC.Sensors');
define ('XAPHEARTBEAT',60);
define ('XAPPORT',3639);
define ('XAPRXTIMEOUT',5.0);

# debug values (can be ORed together)
define ('XAP_DEBUG_ID',2);

define ('SENSORPOLLTIME',60); //how often to read sensors
define ('SENSORPATH','/sys/devices/w1_bus_master1');
define ('SENSORLIST',SENSORPATH.'/w1_master_slaves');

define('KERNELMODULES','ds2482,w1_therm'); //list of required kernel modules for 1 wire sensor types
