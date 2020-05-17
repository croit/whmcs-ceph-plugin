<?php
/**
 * The MIT License (MIT)
 * Copyright (c) 2020 croit GmbH
 * 
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 * 
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 * 
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
 * EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF
 * MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT.
 * IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM,
 * DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR
 * OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE
 * OR OTHER DEALINGS IN THE SOFTWARE.
 *
 * @author     WHMCS Global Services Ltd
 * @copyright  2020 croit GmbH
 * @license    https://opensource.org/licenses/MIT MIT-License
 * @link       https://github.com/croit
 */
 
use WHMCS\Database\Capsule;

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}
//DailyCronJob
add_hook('DailyCronJob', 1, function($vars) {
	$getcredits = Capsule::select("SELECT clientid, SUM(amount) as totalcredit FROM tblcredit GROUP BY clientid"); 
	$credit_avail = 0;
	foreach ($getcredits as  $value) {
		$getclient = Capsule::table('tblclients')->where('id',$value->clientid)->first();

		 $client_name = $getclient->firstname ;
		 $avil_credits = $value->totalcredit ;

	 	if($avil_credits <= 10){
	 		$command = 'SendEmail';
			$postData = array(
				'messagename' => 'Low credit balance',
			    'id' => $value->clientid,			     
			    'customvars' => base64_encode(serialize(array("avil_credits"=>$avil_credits))),
			);
			$adminUsername = '';
			$results = localAPI($command, $postData, $adminUsername);

	 	}

	}
 
});

?>
