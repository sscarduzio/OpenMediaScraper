<?php

/**
 * Arada SMS Application Server
 *
 * GoodMoTest (created: Aug 1, 2011)
 * @author Simone Scarduzio
 * tags
 */

require_once('simpletest/autorun.php');

function captureOutput($buffer = null){
	if($buffer == null){
		ob_start("captureOutput");
		include('../../main.php');
		ob_end();
	}
	else{
		return $buffer;
	}
}
class GoodMoTest extends UnitTestCase{
	
	
	function testBasic(){
		$_REQUEST['sender'] = "079298680";
		$_REQUEST['receiver'] = "1111";
		$_REQUEST['message'] = "111755077";
		$_REQUEST['receivedtime'] = time();
		$res = captureOutput();
		$this->assert("", $res,"MT+ was different than expected");
	}
}
