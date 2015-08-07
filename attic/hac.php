#!/usr/bin/php
<?php

include("lib/defines.inc.php");
include("lib/functions.inc.php");

$running=array('start'=>1,'resource_id'=>0,'status'=>array(),'pipes'=>array());
$stopped=array('start'=>0,'resource_id'=>0,'status'=>array(),'pipes'=>array());

$processes=array(	'io'=>		array('cmd'=>'hac_io.php','args'=>'-d','start'=>1,'resource_id'=>0,'status'=>array(),'pipes'=>array()),
									'dmx'=>		array('cmd'=>'hac_dmx.php','args'=>'-d','start'=>1,'resource_id'=>0,'status'=>array(),'pipes'=>array()),
									'sensor'=>array('cmd'=>'hac_sensor.php','args'=>'-d','start'=>1,'resource_id'=>0,'status'=>array(),'pipes'=>array()),
									'cmd'=>		array('cmd'=>'hac_cmd.php','args'=>'-d','start'=>1,'resource_id'=>0,'status'=>array(),'pipes'=>array())
								);

$startup_dir=dirname(__FILE__);

while(1) {
	if($must_exit) break;
	usleep(1000000);
}
exit(0);

//startup processes:
while ($must_exit===0) {

	foreach($processes as $pn=>$pe) {
		$descriptorspec = array(
		   0 => array("pipe", "r"),  // stdin is a pipe that the child will read from
		   1 => array("file", $startup_dir.'/log/'.$pn.'.out.log', "a"),  // stdout is a pipe that the child will write to
		   2 => array("file", $startup_dir.'/log/'.$pn.'.err.log', "a") // stderr is a file to write to
		);

		if($processes[$pn]['start']===1) {
			if($processes[$pn]['resource_id']===0) {
				$exe=sprintf("%s/%s %s",$startup_dir,$processes[$pn]['cmd'],$processes[$pn]['args']);
				logformat("Starting process '$exe'\n");
				$rp=proc_open($exe,$descriptorspec,$processes[$pn]['pipes'],$startup_dir);
				if(is_resource($rp)) {
				  stream_set_blocking($processes[$pn]['pipes'][0], 0);
					$processes[$pn]['resource_id']=$rp;
					$processes[$pn]['status']=proc_get_status($rp);
					print_r($processes[$pn]);
				} else logformat("Error Starting process '$exe'\n");
			} else { //check if process still running
				$processes[$pn]['status']=proc_get_status($processes[$pn]['resource_id']);
				print_r($processes[$pn]);
			}
		}
		if($must_exit) break;
		sleep(1);
	}
	if($must_exit) break;
	sleep(10);
}
foreach($processes as $pn=>$pe) {
	proc_terminate($processes[$pn]['resource_id']);
}


