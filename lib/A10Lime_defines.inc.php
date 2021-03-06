<?php

define('DEVICE','A10Lime');

/* SUNXI GPIO number definitions */
for($i=0;$i<32;$i++) {
	$SUNXI_GPA[$i]=SUNXI_GPIO_A_START + $i;
	$SUNXI_GPB[$i]=SUNXI_GPIO_B_START + $i;
	$SUNXI_GPC[$i]=SUNXI_GPIO_C_START + $i;
	$SUNXI_GPD[$i]=SUNXI_GPIO_D_START + $i;
	$SUNXI_GPE[$i]=SUNXI_GPIO_E_START + $i;
	$SUNXI_GPF[$i]=SUNXI_GPIO_F_START + $i;
	$SUNXI_GPG[$i]=SUNXI_GPIO_G_START + $i;
	$SUNXI_GPH[$i]=SUNXI_GPIO_H_START + $i;
	$SUNXI_GPI[$i]=SUNXI_GPIO_I_START + $i;
}

//$GPIO are pin names on each connector mapped to pin_id used by sunxi funtions

$GPIO['lcd']	=array(	"PD16"=>$SUNXI_GPD[16],
											"PD17"=>$SUNXI_GPD[17],
											"PD18"=>$SUNXI_GPD[18],
											"PD19"=>$SUNXI_GPD[19],
											"PD20"=>$SUNXI_GPD[20],
											"PD21"=>$SUNXI_GPD[21],
											"PD22"=>$SUNXI_GPD[22],
											"PD23"=>$SUNXI_GPD[23],
											"PD8"=>$SUNXI_GPD[8],
											"PD9"=>$SUNXI_GPD[9],
											"PD10"=>$SUNXI_GPD[10],
											"PD11"=>$SUNXI_GPD[11],
											"PD12"=>$SUNXI_GPD[12],
											"PD13"=>$SUNXI_GPD[13],
											"PD14"=>$SUNXI_GPD[14],
											"PD15"=>$SUNXI_GPD[15],
											"PD0"=>$SUNXI_GPD[0],
											"PD1"=>$SUNXI_GPD[1],
											"PD2"=>$SUNXI_GPD[2],
											"PD3"=>$SUNXI_GPD[3],
											"PD4"=>$SUNXI_GPD[4],
											"PD5"=>$SUNXI_GPD[5],
											"PD6"=>$SUNXI_GPD[6],
											"PD7"=>$SUNXI_GPD[7],
											"PD26"=>$SUNXI_GPD[26],
											"PD27"=>$SUNXI_GPD[27],
											"PD24"=>$SUNXI_GPD[24],
											"PD25"=>$SUNXI_GPD[25],
											"PH8"=>$SUNXI_GPH[8],
											"PB2"=>$SUNXI_GPB[2]);

$GPIO['gpio1']=array(	"PG0"=>$SUNXI_GPG[0],
											"PG1"=>$SUNXI_GPG[1],
											"PG2"=>$SUNXI_GPG[2],
											"PG3"=>$SUNXI_GPG[3],
											"PG4"=>$SUNXI_GPG[4],
											"PG5"=>$SUNXI_GPG[5],
											"PG6"=>$SUNXI_GPG[6],
											"PG7"=>$SUNXI_GPG[7],
											"PG8"=>$SUNXI_GPG[8],
											"PG9"=>$SUNXI_GPG[9],
											"PG10"=>$SUNXI_GPG[10],
											"PG11"=>$SUNXI_GPG[11],
											"PC3"=>$SUNXI_GPC[3],
											"PC18"=>$SUNXI_GPC[18],
											"PC19"=>$SUNXI_GPC[19],
											"PC20"=>$SUNXI_GPC[20],
											"PC21"=>$SUNXI_GPC[21],
											"PC22"=>$SUNXI_GPC[22],
											"PC23"=>$SUNXI_GPC[23],
											"PC24"=>$SUNXI_GPC[24],
											"PB18"=>$SUNXI_GPB[18], //also TWI1_SCL
											"PB19"=>$SUNXI_GPB[19],	//also TWI1_SDA
											"PB20"=>$SUNXI_GPB[20], //also TWI2_SCL
											"PB21"=>$SUNXI_GPB[21]);//also TWI2_SDA

$GPIO['gpio2']=array(	"PI0"=>$SUNXI_GPI[0],
											"PI1"=>$SUNXI_GPI[1],
											"PI2"=>$SUNXI_GPI[2],
											"PI3"=>$SUNXI_GPI[3],
											"PI4"=>$SUNXI_GPI[4],
											"PI5"=>$SUNXI_GPI[5],
											"PI6"=>$SUNXI_GPI[6],
											"PI7"=>$SUNXI_GPI[7],
											"PI8"=>$SUNXI_GPI[8],
											"PI9"=>$SUNXI_GPI[9],
											"PI10"=>$SUNXI_GPI[10],
											"PI11"=>$SUNXI_GPI[11],
											"PI12"=>$SUNXI_GPI[12],
											"PI13"=>$SUNXI_GPI[13],
											"PI14"=>$SUNXI_GPI[14],
											"PI15"=>$SUNXI_GPI[15],
											"PI16"=>$SUNXI_GPI[16],
											"PI17"=>$SUNXI_GPI[17],
											"PI18"=>$SUNXI_GPI[18],
											"PI19"=>$SUNXI_GPI[19],
											"PI20"=>$SUNXI_GPI[20],
											"PI21"=>$SUNXI_GPI[21],
											"PE0"=>$SUNXI_GPE[0],
											"PE1"=>$SUNXI_GPE[1],
											"PE2"=>$SUNXI_GPE[2],
											"PE3"=>$SUNXI_GPE[3],
											"PE4"=>$SUNXI_GPE[4],
											"PE5"=>$SUNXI_GPE[5],
											"PE6"=>$SUNXI_GPE[6],
											"PE7"=>$SUNXI_GPE[7],
											"PE8"=>$SUNXI_GPE[8],
											"PE9"=>$SUNXI_GPE[9],
											"PE10"=>$SUNXI_GPE[10],
											"PE11"=>$SUNXI_GPE[11]);

$GPIO['gpio3']=array(	"PH0"=>$SUNXI_GPH[0],
											"PH7"=>$SUNXI_GPH[7],
											"PH9"=>$SUNXI_GPH[9],
											"PH10"=>$SUNXI_GPH[10],
											"PH11"=>$SUNXI_GPH[11],
											"PH12"=>$SUNXI_GPH[12],
											"PH13"=>$SUNXI_GPH[13],
											"PH14"=>$SUNXI_GPH[14],
											"PH15"=>$SUNXI_GPH[15],
											"PH16"=>$SUNXI_GPH[16],
											"PH17"=>$SUNXI_GPH[17],
											"PH18"=>$SUNXI_GPH[18],
											"PH19"=>$SUNXI_GPH[19],
											"PH20"=>$SUNXI_GPH[20],
											"PH21"=>$SUNXI_GPH[21],
											"PH22"=>$SUNXI_GPH[22],
											"PH23"=>$SUNXI_GPH[23],
											"PH24"=>$SUNXI_GPH[24],
											"PH25"=>$SUNXI_GPH[25],
											"PH26"=>$SUNXI_GPH[26],
											"PH27"=>$SUNXI_GPH[27],
											"PB3"=>$SUNXI_GPB[3],
											"PB4"=>$SUNXI_GPB[4],
											"PB5"=>$SUNXI_GPB[5],
											"PB6"=>$SUNXI_GPB[6],
											"PB7"=>$SUNXI_GPB[7],
											"PB8"=>$SUNXI_GPB[8],
											"PB10"=>$SUNXI_GPB[10],
											"PB11"=>$SUNXI_GPB[11],
											"PB12"=>$SUNXI_GPB[12],
											"PB13"=>$SUNXI_GPB[13],
											"PB14"=>$SUNXI_GPB[14],
											"PB15"=>$SUNXI_GPB[15],
											"PB16"=>$SUNXI_GPB[16],
											"PB17"=>$SUNXI_GPB[17]);

$GPIO['gpio4']=array(	"PC7"=>$SUNXI_GPC[7],
											"PC16"=>$SUNXI_GPC[16],
											"PC17"=>$SUNXI_GPC[17]);

$GPIO['led']	=array(	"PH2"=>$SUNXI_GPH[2]);

//$PIN are pin names (without reference to the connector) mapped to pin_id used by sunxi funtions
$PIN=array_merge($GPIO['lcd'],$GPIO['gpio1'],$GPIO['gpio2'],$GPIO['gpio3'],$GPIO['gpio4'],$GPIO['led']);

//$CONN are physical pin numbers on each connector mapped to pin_id used by sunxi funtions
$CONN["lcd"]	=array(	"5"=>$SUNXI_GPD[16],
											"6"=>$SUNXI_GPD[17],
											"7"=>$SUNXI_GPD[18],
											"8"=>$SUNXI_GPD[19],
											"9"=>$SUNXI_GPD[20],
											"10"=>$SUNXI_GPD[21],
											"11"=>$SUNXI_GPD[22],
											"12"=>$SUNXI_GPD[23],
											"13"=>$SUNXI_GPD[8],
											"14"=>$SUNXI_GPD[9],
											"15"=>$SUNXI_GPD[10],
											"16"=>$SUNXI_GPD[11],
											"17"=>$SUNXI_GPD[12],
											"18"=>$SUNXI_GPD[13],
											"19"=>$SUNXI_GPD[14],
											"20"=>$SUNXI_GPD[15],
											"21"=>$SUNXI_GPD[0],
											"22"=>$SUNXI_GPD[1],
											"23"=>$SUNXI_GPD[2],
											"24"=>$SUNXI_GPD[3],
											"25"=>$SUNXI_GPD[4],
											"26"=>$SUNXI_GPD[5],
											"27"=>$SUNXI_GPD[6],
											"28"=>$SUNXI_GPD[7],
											"29"=>$SUNXI_GPD[26],
											"30"=>$SUNXI_GPD[27],
											"31"=>$SUNXI_GPD[24],
											"32"=>$SUNXI_GPD[25],
											"35"=>$SUNXI_GPH[8],
											"36"=>$SUNXI_GPB[2]);

$CONN['gpio1']=array(	"5"=>$SUNXI_GPG[0],
											"7"=>$SUNXI_GPG[1],
											"9"=>$SUNXI_GPG[2],
											"11"=>$SUNXI_GPG[3],
											"13"=>$SUNXI_GPG[4],
											"15"=>$SUNXI_GPG[5],
											"17"=>$SUNXI_GPG[6],
											"19"=>$SUNXI_GPG[7],
											"21"=>$SUNXI_GPG[8],
											"23"=>$SUNXI_GPG[9],
											"25"=>$SUNXI_GPG[10],
											"27"=>$SUNXI_GPG[11],
											"29"=>$SUNXI_GPC[3],
											"31"=>$SUNXI_GPC[18],
											"33"=>$SUNXI_GPC[19],
											"35"=>$SUNXI_GPC[20],
											"37"=>$SUNXI_GPC[21],
											"39"=>$SUNXI_GPC[22],
											"40"=>$SUNXI_GPC[23],
											"38"=>$SUNXI_GPC[24],
											"36"=>$SUNXI_GPB[18],
											"34"=>$SUNXI_GPB[19],
											"32"=>$SUNXI_GPB[20],
											"30"=>$SUNXI_GPB[21]);

$CONN['gpio2']=array(	"9"=>$SUNXI_GPI[0],
											"11"=>$SUNXI_GPI[1],
											"13"=>$SUNXI_GPI[2],
											"15"=>$SUNXI_GPI[3],
											"17"=>$SUNXI_GPI[4],
											"19"=>$SUNXI_GPI[5],
											"21"=>$SUNXI_GPI[6],
											"23"=>$SUNXI_GPI[7],
											"25"=>$SUNXI_GPI[8],
											"27"=>$SUNXI_GPI[9],
											"29"=>$SUNXI_GPI[10],
											"31"=>$SUNXI_GPI[11],
											"33"=>$SUNXI_GPI[12],
											"35"=>$SUNXI_GPI[13],
											"37"=>$SUNXI_GPI[14],
											"39"=>$SUNXI_GPI[15],
											"40"=>$SUNXI_GPI[16],
											"38"=>$SUNXI_GPI[17],
											"36"=>$SUNXI_GPI[18],
											"34"=>$SUNXI_GPI[19],
											"32"=>$SUNXI_GPI[20],
											"30"=>$SUNXI_GPI[21],
											"6"=>$SUNXI_GPE[0],
											"8"=>$SUNXI_GPE[1],
											"10"=>$SUNXI_GPE[2],
											"12"=>$SUNXI_GPE[3],
											"14"=>$SUNXI_GPE[4],
											"16"=>$SUNXI_GPE[5],
											"18"=>$SUNXI_GPE[6],
											"20"=>$SUNXI_GPE[7],
											"22"=>$SUNXI_GPE[8],
											"24"=>$SUNXI_GPE[9],
											"26"=>$SUNXI_GPE[10],
											"28"=>$SUNXI_GPE[11]);

$CONN['gpio3']=array(	"7"=>$SUNXI_GPH[0],
											"9"=>$SUNXI_GPH[7],
											"11"=>$SUNXI_GPH[9],
											"13"=>$SUNXI_GPH[10],
											"15"=>$SUNXI_GPH[11],
											"17"=>$SUNXI_GPH[12],
											"19"=>$SUNXI_GPH[13],
											"21"=>$SUNXI_GPH[14],
											"23"=>$SUNXI_GPH[15],
											"25"=>$SUNXI_GPH[16],
											"27"=>$SUNXI_GPH[17],
											"29"=>$SUNXI_GPH[18],
											"31"=>$SUNXI_GPH[19],
											"33"=>$SUNXI_GPH[20],
											"35"=>$SUNXI_GPH[21],
											"37"=>$SUNXI_GPH[22],
											"39"=>$SUNXI_GPH[23],
											"34"=>$SUNXI_GPH[24],
											"36"=>$SUNXI_GPH[25],
											"38"=>$SUNXI_GPH[26],
											"40"=>$SUNXI_GPH[27],
											"6"=>$SUNXI_GPB[3],
											"8"=>$SUNXI_GPB[4],
											"10"=>$SUNXI_GPB[5],
											"12"=>$SUNXI_GPB[6],
											"14"=>$SUNXI_GPB[7],
											"16"=>$SUNXI_GPB[8],
											"18"=>$SUNXI_GPB[10],
											"20"=>$SUNXI_GPB[11],
											"22"=>$SUNXI_GPB[12],
											"24"=>$SUNXI_GPB[13],
											"26"=>$SUNXI_GPB[14],
											"28"=>$SUNXI_GPB[15],
											"30"=>$SUNXI_GPB[16],
											"32"=>$SUNXI_GPB[17]);

$CONN['gpio4']=array(	"16"=>$SUNXI_GPC[7],
											"18"=>$SUNXI_GPC[16],
											"20"=>$SUNXI_GPC[17]);

$CONN['led']	=array(	"1"=>$SUNXI_GPH[2]);

$GPIO_TYPE=array('GPIO_INPUT','GPIO_OUTPUT','GPIO_PER');

foreach($PIN as $p=>$v) define('PIN_'.$p,$v);
foreach($CONN as $c=>$a) {
	foreach($a as $p=>$v) define(strtoupper($c).'_'.$p,$v);
}

define('GPIO_INPUT',SUNXI_GPIO_INPUT);
define('GPIO_OUTPUT',SUNXI_GPIO_OUTPUT);
define('GPIO_PER',SUNXI_GPIO_PER);
define('GPIO_PULL_NONE',SUNXI_PULL_NONE);
define('GPIO_PULL_UP',SUNXI_PULL_UP);
define('GPIO_PULL_DOWN',SUNXI_PULL_DOWN);
define('GPIO_LOW',0);
define('GPIO_HIGH',1);

function GPIO_init() {
	$r=sunxi_gpio_init();
	if($r<0) die("Failed to open GPIO. Are you running as root?\n");
	return $r;
}
function GPIO_input($pin) {
	return sunxi_gpio_input($pin);
}
function GPIO_pinmode($pin,$val) {
	return sunxi_gpio_set_cfgpin($pin,$val);
}
function GPIO_setcfg($pin,$val) {
	return sunxi_gpio_set_cfgpin($pin,$val);
}
function GPIO_getcfg($pin) {
	return sunxi_gpio_get_cfgpin($pin);
}
function GPIO_output($pin,$val) {
	return sunxi_gpio_output($pin,$val);
}
function GPIO_pullup($pin,$pull) {
	return sunxi_gpio_pullup($pin,$pull);
}

function I2C_Read($fd) {
	return A10LimeI2CRead($fd);
}
function I2C_ReadReg8($fd,$reg) {
	return A10LimeI2CReadReg8($fd,$reg);
}
function I2C_ReadReg16($fd,$reg) {
	return A10LimeI2CReadReg16($fd,$reg);
}
function I2C_Write($fd,$data) {
	return A10LimeI2CWrite($fd,$data);
}
function I2C_WriteReg8($fd,$reg,$data) {
	return A10LimeI2CWriteReg8($fd,$reg,$data);
}
function I2C_WriteReg16($fd,$reg,$data) {
	return A10LimeI2CWriteReg16($fd,$reg,$data);
}
function I2C_SetupInterface($device,$devId) {
	$r=A10LimeI2CSetupInterface($device,$devId);
	if($r<0) die("Failed to open I2C. Are you running as root?\n");
	return $r;
}
function I2C_Setup($devId) {
	return A10LimeI2CSetup($devId);
}

function SPI_Open($device,$mode,$bits_per_word,$speed) {
	return A10LimeSPIOpen($device,$mode,$bits_per_word,$speed);
}
function SPI_Close($fd) {
	return A10LimeSPIClose($fd);
}
function SPI_Xfer($fd,$tx_buffer,$tx_len,$rx_buffer,$rx_len) {
	return A10LimeSPIXfer($fd,$tx_buffer,$tx_len,$rx_buffer,$rx_len);
}
function SPI_Read($fd,$rx_buffer,$rx_len) {
	return A10LimeSPIRead($fd,$rx_buffer,$rx_len);
}
function SPI_Write($fd,$tx_buffer,$tx_len) {
	return A10LimeSPIWrite($fd,$tx_buffer,$tx_len);
}

function Serial_Open($device,$baud) {
	return A10LimeSerialOpen($device,$baud);
}
function Serial_Close($fd) {
	A10LimeSerialClose($fd);
}
function Serial_Flush($fd) {
	A10LimeSerialFlush($fd);
}
function Serial_Putchar($fd,$c) {
	A10LimeSerialPutchar($fd,$c);
}
function Serial_Puts($fd,$s) {
	A10LimeSerialPuts($fd,$s);
}
function Serial_Printf($fd,$message) {
	A10LimeSerialPrintf($fd,$message);
}
function Serial_DataAvail($fd) {
	return A10LimeSerialDataAvail($fd);
}
function Serial_Getchar($fd) {
	return A10LimeSerialGetchar($fd);
}

?>