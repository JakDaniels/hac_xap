<?php
# different types of switches that could be connected
define ('SW_NONE',		0x00);	//no switch connected
define ('SW_1P_M',		0x01); 	//1 pole momentary action switch (connected to down multiplex line)
define ('SW_2P_M',		0x02); 	//2 pole momentary action switch (up line NC, down line NO)
define ('SW_2P_M_CO',	0x03); 	//2 pole momentary action switch, centre off
define ('SW_1P_L',		0x04); 	//1 pole latching switch (connected to down multiplex line) \
define ('SW_2P_L',		0x05); 	//2 pole latching switch																		| not yet implemented
define ('SW_2P_L_CO',	0x06); 	//2 pole latching switch, centre off												/

# outputs (lights) are all relays, we must make sure they don't try to change state too quickly
define ('OUT_NODELAY',0x00);	//no output delay
define ('OUT_DELAY1',	0x01);	//relay can only change state every 250ms
define ('OUT_DELAY2',	0x02);	//relay can only change state every 500ms
define ('OUT_DELAY3',	0x03);	//relay can only change state every 750ms
define ('OUT_DELAY4',	0x04);	//relay can only change state every 1000ms (1sec)

# roughly how often in ms do we poll all the switches (min 10)?
define ('POLLINTERVAL',50);
# roughly how long in ms does it take for a depressed switch to indicate HOLD mode. (should be > POLLINTERVAL)
define ('HOLDINTERVAL',1000);

# define which gpio pins we will use for the switch up/down multiplex line
if(IO_LIBRARY=='wiringPI') {
	define ('GPIO_PIN_UP',4);
	define ('GPIO_PIN_DN',5);
}
if(IO_LIBRARY=='A10Lime') {
	define ('GPIO_PIN_UP',PIN_PG0);
	define ('GPIO_PIN_DN',PIN_PG1);
}

//registers on the PCA9555
define('PCA9555_INPORT0',		0x00);
define('PCA9555_INPORT1',		0x01);
define('PCA9555_OUTPORT0',	0x02);
define('PCA9555_OUTPORT1',	0x03);
define('PCA9555_IPOLPORT0',	0x04);
define('PCA9555_IPOLPORT1',	0x05);
define('PCA9555_CONFPORT0',	0x06);
define('PCA9555_CONFPORT1',	0x07);
# PCA9555 i/o board, 16bits each
define('PCA9555_ADDR1',0x20);
define('PCA9555_ADDR2',0x21);
# OPTO PCF8574 based input boards, 8 bits each
define('PCF8574_ADDR1',0x24);
define('PCF8574_ADDR2',0x25);
define('PCF8574_ADDR3',0x26);
define('PCF8574_ADDR4',0x27);
