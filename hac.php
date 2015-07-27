#!/usr/bin/php
<?php

include("lib/defines.inc.php");
include("lib/functions.inc.php");

$running=array('start'=>1,'resource_id'=>0,'status'=>array());
$stopped=array('start'=>0,'resource_id'=>0,'status'=>array());

$processes=array( 'hac_io.php'					=>$running,
									'hac_dmx.php'					=>$running,
									'hac_temperature.php'	=>$stopped,
									'hac_cmd.php'					=>$running );
									
$startup_dir=dirname(__FILE__);
									
//startup processes:

foreach($processes as $pn=>$pe) {
	if($pe['start']===1) {
		if($rp=proc_open(sprintf("exec %s/%s ",$startup_dir,$pn))) {
			$status=proc_get_status($rp);
			$processes[$pn]['resource_id']=$rp;
			$processes[$pn]['status']=proc_get_status($rp);
		}
	}
}







posix_getpgid($pid);		
