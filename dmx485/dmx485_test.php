#!/usr/bin/php
<?php
include("../lib/functions.inc.php");
declare(ticks=10); //must do this in top level file, not in an include as of php 5.3

define ('CURSOR_UP',"\033[%dA");
define ('CURSOR_DOWN',"\033[%dB");
// shared memory buffer and semaphore key
define ('DMX_SHM_KEY',0x0000FF30);
define ('DMX_SEM_KEY',0x0000FF40);

//Create the semaphore
if(!$sem_id=sem_get(DMX_SEM_KEY, 1)) {
    logformat(sprintf("Could not open shared memory semaphore with key: %08X",DMX_SEM_KEY));
    exit(1);
}

if(!$shm_id=shmop_open(DMX_SHM_KEY,'c',0, 512)) {
	logformat(sprintf("Could not open shared memory with key: %08X",DMX_SHM_KEY));
	exit(1);
}

if($debug) {
	printf("Size: %d\n",shmop_size($shm_id));
	print `ipcs`;
	if($s=shmop_read($shm_id,0,512)) hex_print($s,0);
}

$c=255;
$nc=512/4;

for($i=0;$i<$nc;$i++) {
	$r2[$i]=0;
	$g2[$i]=0;
	$b2[$i]=0;
	$r1[$i]=rand(0,255);
	$g1[$i]=rand(0,255);
	$b1[$i]=rand(0,255);
	$s[$i*4]=chr($c);
	$s[$i*4+1]=chr($r2[$i]);
	$s[$i*4+2]=chr($g2[$i]);
	$s[$i*4+3]=chr($b2[$i]);
}

while($must_exit===0) {
	for($i=0;$i<$nc;$i++) {
		$rd=0-($r2[$i]>$r1[$i])+($r2[$i]<$r1[$i]);
		$gd=0-($g2[$i]>$g1[$i])+($g2[$i]<$g1[$i]);
		$bd=0-($b2[$i]>$b1[$i])+($b2[$i]<$b1[$i]);
		if($rd==0) $r1[$i]=rand(0,1)?rand(0,31):rand(224,255); else $r2[$i]+=$rd;
		if($gd==0) $g1[$i]=rand(0,1)?rand(0,31):rand(224,255); else $g2[$i]+=$gd;
		if($bd==0) $b1[$i]=rand(0,1)?rand(0,31):rand(224,255); else $b2[$i]+=$bd;
		$s[$i*4]=chr($c);
		$s[$i*4+1]=chr($r2[$i]);
		$s[$i*4+2]=chr($g2[$i]);
		$s[$i*4+3]=chr($b2[$i]);
	}
	shmop_write_with_lock($shm_id,$sem_id,$s,0);
	if($debug&2) hex_print($s,1);
	usleep(40000);
}
$done=0;
while(!$done) {
	$done=1;
	for($i=0;$i<$nc;$i++) {
		if($r2[$i]) $r2[$i]--;
		if($g2[$i]) $g2[$i]--;
		if($b2[$i]) $b2[$i]--;
		if($r2[$i] or $g2[$i] or $b2[$i]) $done=0;
		$s[$i*4]=chr($c);
		$s[$i*4+1]=chr($r2[$i]);
		$s[$i*4+2]=chr($g2[$i]);
		$s[$i*4+3]=chr($b2[$i]);
	}
	shmop_write_with_lock($shm_id,$sem_id,$s,0);
	if($debug&2) hex_print($s,1);
	usleep(10000);
}
shmop_close($shm_id);
//sem_remove($sem_id);

function shmop_write_with_lock($shm_id,$sem_id,$s,$offset=0) {
	//Acquire the semaphore
	if (!sem_acquire($sem_id)) {	//If not available this will stall until the semaphore is released by the other process
    logformat("Failed to acquire shared memory semaphore!\n");
    sem_remove($sem_id);						//Use even if we didn't create the semaphore as something has gone wrong and its usually debugging so lets no lock up this semaphore key
    exit(1);
	}
	shmop_write($shm_id,$s,$offset);
	//Release the semaphore
	if (!sem_release($sem_id)) { //Must be called after sem_acquire() so that another process can acquire the semaphore
    logformat("Failed to release shared memory semaphore!\n");
	}
}

function hex_print($s,$overwrite=0) {
	if($overwrite) printf(CURSOR_UP, strlen($s)/32+1);
	for($i=0;$i<strlen($s);$i++) {
		if($i%32==0) printf("\n%03X: ",$i);
		printf("%02X ",ord($s[$i]));
	}
	print "\n";
}
