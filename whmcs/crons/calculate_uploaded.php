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
$whmcspath = "";
if (!empty($whmcspath)) {
    require $whmcspath . "/init.php";
}
else {
    require ("../init.php");
}

$serverModule = 'croit';
$domainStatusGet = Capsule::table('tblhosting')->where('domainstatus', 'Active')
    ->get();
if (!empty($domainStatusGet)) {
    if (!Capsule::Schema()->hasTable('mod_croit_data_usage')) {
        try {
            Capsule::schema()->create('mod_croit_data_usage', function ($table) {
                $table->increments('id');
                $table->string('owner');
                $table->string('bucketName');
                $table->string('bytesUploaded')
                    ->nullable();
                $table->string('bytesDownloaded')
                    ->nullable();
            });
        }
        catch(\Exception $e) {
            logActivity('Unable to create table  Error: ' . $e->getMessage());
        }
    }
    $adminuserFetch = Capsule::table('tbladmins')->select('username', 'id')
        ->where('roleid', 1)
        ->first();
    $adminuser = $adminuserFetch->username;
    $admin_id = $adminuserFetch->id;
    if (Capsule::Schema()
        ->hasTable('mod_croit_data_usage')) {
        foreach ($domainStatusGet as $domainData) {
            $userIdForItem = $domainData->userid;

            #get credits
            $getcredits = Capsule::table('tblcredit')->where('clientid', $userIdForItem)->get();
            //print_r($getcredits);
            $calculate_crdt = 0;
            foreach ($getcredits as $value) {
                $credits = $value->amount;
                $calculate_crdt = $credits + $calculate_crdt;

            }
            //echo $calculate_crdt;die;
            $getCroitModuleData = Capsule::table('tblproducts')->where('servertype', $serverModule)->where('id', $domainData->packageid)
                ->first();
            if ($getCroitModuleData) {
                $getQuotaLimitUpload = $getCroitModuleData->configoption10;
                $getPricePerGbUpload = $getCroitModuleData->configoption11;
                $getPricePerGbDownload = $getCroitModuleData->configoption12;
                $curlReqUrl = $getCroitModuleData->configoption1;
                $apiUserName = $getCroitModuleData->configoption2;
                $apiPassword = $getCroitModuleData->configoption3;
                $productId = $getCroitModuleData->id;
                $urlForLoginAuth = $curlReqUrl . 'auth/login-form';
                $postDataReq = 'username=' . $apiUserName . '&password=' . $apiPassword . '&grant_type=password';
                $loginAccessRequest = croit_DoRequest($urlForLoginAuth, 'POST', $postDataReq);
                if ($loginAccessRequest['code'] == 200) {
                    logModuleCall('Croit Provisioning Module', 'login Authorized ', $curlReqUrl, $loginAccessRequest);
                    $getCustomFieldId = Capsule::table('tblcustomfields')->where('relid', $productId)->where('fieldname', 'like', '%croit_uid%')
                        ->first();
                    $getCustomFieldValue = Capsule::table('tblcustomfieldsvalues')->where('fieldid', $getCustomFieldId->id)
                        ->where('relid', $domainData->id)
                        ->first();
                    $userValueForReq = $getCustomFieldValue->value;
                    $urlReqForUser = $curlReqUrl . 's3/usage/by-user/' . $userValueForReq;
                    $authToken = $loginAccessRequest['result']['access_token'];
                    $headersAuth = ["Authorization: Bearer {$authToken} ", "Content-Type: application/json"];
                    $resultUserGetData = croit_DoRequest($urlReqForUser, 'GET', '', $headersAuth);
                    logModuleCall('Croit Provisioning Module', 'get usage data users', $urlReqForUser, $resultUserGetData);
                    if ($resultUserGetData['code'] == 200) {
                        if (!empty($resultUserGetData['result']['buckets'])) {
                            foreach ($resultUserGetData['result']['buckets'] as $bucketDataObject) {
                                foreach ($bucketDataObject as $allBucketData) {
                                    $allBucketDataList[] = $allBucketData;
                                }
                            }
                            /*** start calculate billing part ****/
                            if (!empty($allBucketDataList)) {
                                foreach ($allBucketDataList as $bucketDataForLocal) {
                                    $bucketOwner = $bucketDataForLocal['bucketOwner'];
                                    $bucketName = $bucketDataForLocal['bucketName'];
                                    $bucketDownloadData = wgs_formatSizeUnits($bucketDataForLocal['bytesDownloaded'], 'gb');
                                    $bucketUploadData = wgs_formatSizeUnits($bucketDataForLocal['bytesUploaded'], 'gb');
                                    //$bucketUploadData = wgs_formatSizeUnits(1273741824,'gb');
                                    #Billable item only add if quota exceed in upload case
                                    if ($bucketUploadData > $getQuotaLimitUpload) {
                                        $checkForUserData = Capsule::table('mod_croit_data_usage')->where('bucketName', $bucketName)->count();
                                        if ($checkForUserData == 0) {
                                            try {
                                                $insertdata = ['owner' => $bucketOwner, 'bucketName' => $bucketName, 'bytesUploaded' => $bucketUploadData];
                                                Capsule::table('mod_croit_data_usage')->insert($insertdata);
                                                $remainedGbUploadForToday = $bucketUploadData - $getQuotaLimitUpload;
                                            }
                                            catch(\Exception $e) {
                                                logActivity("Unable to insert: {$e->getMessage() }");
                                            }
                                        }
                                        else {
                                            $getBytesDataDb = Capsule::table('mod_croit_data_usage')->where('bucketName', $bucketName)->first();
                                            $remainedGbUploadForToday = $bucketUploadData - $getBytesDataDb->bytesUploaded;
                                            try {
                                                Capsule::table('mod_croit_data_usage')
                                                    ->where('owner', $bucketOwner)->where('bucketName', $bucketName)->update(['bytesUploaded' => $bucketUploadData]);
                                            }
                                            catch(\Exception $e) {
                                                logActivity("Unable to update: {$e->getMessage() }");
                                            }
                                        }
                                        $priceForUploadExtraGb = $remainedGbUploadForToday * $getPricePerGbUpload;

                                        #Billable item api for upload case
                                        $command = 'AddBillableItem';
                                        $adminUsername = $adminuser;
                                        if ($priceForUploadExtraGb > 0) {
                                            $postDataUpload = array(
                                                'clientid' => $userIdForItem,
                                                'description' => 'Overusage bill for upload data bucket name ' . $bucketName . ' extra usage is ' . $remainedGbUploadForToday . '(GB) * ' . $getPricePerGbUpload . '',
                                                'amount' => $priceForUploadExtraGb,
                                                'invoiceaction' => 'nextcron',
                                            );
                                            $resultsUpload = localAPI($command, $postDataUpload, $adminUsername);
                                            logModuleCall('Croit Provisioning Module', 'Upload Data Billable Item ', $postDataUpload, $resultsUpload);
                                        }
                                    }
                                    #Billable item for download data case
                                    $checkForUserData = Capsule::table('mod_croit_data_usage')->where('bucketName', $bucketName)->count();
                                    if ($checkForUserData == 0) {
                                        try {
                                            $insertdata = ['owner' => $bucketOwner, 'bucketName' => $bucketName, 'bytesDownloaded' => $bucketDownloadData];
                                            Capsule::table('mod_croit_data_usage')->insert($insertdata);
                                            $remainedGbDownloadForToday = $bucketDownloadData;
                                        }
                                        catch(\Exception $e) {
                                            logActivity("Unable to insert: {$e->getMessage() }");
                                        }
                                    }
                                    else {
                                        $getBytesDataDb = Capsule::table('mod_croit_data_usage')->where('bucketName', $bucketName)->first();
                                        $remainedGbDownloadForToday = $bucketDownloadData - $getBytesDataDb->bytesDownloaded;
                                        try {
                                            Capsule::table('mod_croit_data_usage')
                                                ->where('owner', $bucketOwner)->where('bucketName', $bucketName)->update(['bytesDownloaded' => $bucketDownloadData]);
                                        }
                                        catch(\Exception $e) {
                                            logActivity("Unable to update: {$e->getMessage() }");
                                        }
                                    }

                                    $priceForDownloadExtraGb = $remainedGbDownloadForToday * $getPricePerGbDownload;

                                    #price get from credits calculate
                                    //	$priceForDownloadExtraGb = 5;
                                    $remain_credit = $calculate_crdt - $priceForDownloadExtraGb;
                                    //	$remain_Bill	= $priceForDownloadExtraGb - $calculate_crdt;
                                    $todaydate = date("Y-m-d");

                                    Capsule::table('tblcredit')->where('clientid', $userIdForItem)->delete();

                                    $insertdata = ['clientid' => $userIdForItem, 'admin_id' => $admin_id, 'date' => $todaydate, 'description' => 'credits balance', 'amount' => $remain_credit];
                                    Capsule::table('tblcredit')->insert($insertdata);
                                    //	print_r($insertdata);
                                    

                                    #Billable item add in download case
                                    /* 		if($priceForDownloadExtraGb > 0){
                                    $postDataDownload = array(
                                    'clientid' => $userIdForItem,
                                    'description' => 'usage bill for bucket name '.$bucketName.' Today usage for download is '.$remainedGbDownloadForToday.'(GB) * '.$getPricePerGbDownload.'',
                                    'amount' => $priceForDownloadExtraGb,
                                    'invoiceaction' => 'nextcron',
                                    );
                                    $resultsDownload = localAPI($command, $postDataDownload, $adminUsername);
                                    logModuleCall('Croit Provisioning Module', 'Download Data Billable Item ', $postDataDownload, $resultsDownload);
                                    } */
                                }
                            }
                            /*** end calculate billing part ****/
                        }
                    }
                    else {
                        logModuleCall('Croit Provisioning Module', 'get usage data users failed ', $urlReqForUser, $resultUserGetData);
                    }
                }
                else {
                    logModuleCall('Croit Provisioning Module', 'login Failed ', $curlReqUrl, $loginAccessRequest);
                }
            }
        }
    }
}

function croit_DoRequest($url, $method, $postData = NULL, $headers = false) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    if ($headers) {
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    }
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    if ($method == 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
    }
    if ($method == 'GET') {
        curl_setopt($ch, CURLOPT_HTTPGET, true);
    }
    if ($method == 'DELETE') {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    }
    if ($method == 'PUT') {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
    }
    if ($method == 'PATCH') {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
    }
    $response = curl_exec($ch);
    $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ['code' => $httpcode, 'result' => json_decode($response, true) ];
}

function wgs_calculate_price_increase($quota_limit, $price_per_gb, $alldata) {
    foreach ($alldata as $key => $value) {
        $owner = $value['bucketOwner'];
        $bucketname = $value['bucketName'];
        $bytesDownloaded = $value['bytesDownloaded'];
        $bytesUploaded = $value['bytesUploaded'];
        $Exist = Capsule::table('mod_croit_data_usage')->where('bucketName', $bucketname)->first();

        if (empty($Exist)) {
            try {
                $insertdata = ['owner' => $owner, 'bucketName' => $bucketname, 'bytesDownloaded' => $bytesDownloaded, 'bytesUploaded' => $bytesUploaded, ];

                Capsule::table('mod_croit_data_usage')->insert($insertdata);

            }
            catch(\Exception $e) {
                logActivity("Unable to insert: {$e->getMessage() }");
            }
        }
        else {
            try {
                $updatedUserCount = Capsule::table('mod_croit_data_usage')->where('owner', $owner)->where('bucketName', $bucketname)->update(['bytesDownloaded' => $bytesDownloaded, 'bytesUploaded' => $bytesUploaded, ]);

            }
            catch(\Exception $e) {
                echo "Unable to update: {$e->getMessage() }";
            }

        }

        $getbytes = Capsule::table('mod_croit_data_usage')->where('bucketName', $bucketname)->first();

        $byte_get_from_db = $getbytes->bytesDownloaded;
        if ($bytesDownloaded > 1000000000) {
            $final_bytedownloaded = $bytesDownloaded - $byte_get_from_db;

        }
        $data_inGb = 5000000000 / 1000000000;

        if ($data_inGb > $quota_limit) {
            $getdata = $data_inGb - 1;

        }
    }

    $command = 'AddBillableItem';
    $postData = array(
        'clientid' => '1',
        'description' => 'This is a billable item',
        'amount' => '10.00',
        'invoiceaction' => 'recur',
        'recur' => '1',
        'recurcycle' => 'Months',
        'recurfor' => '12',
        'duedate' => '2016-01-01',
    );
    $adminUsername = 'ADMIN_USERNAME'; // Optional for WHMCS 7.2 and later
    $results = localAPI($command, $postData, $adminUsername);
    //print_r($results);
    

    
}
function wgs_formatSizeUnits($bytes, $for) {
    if ($for == 'tb') {
        $bytes = number_format($bytes / 1099511627776, 2);
    }
    else if ($for == 'gb') {
        $bytes = number_format($bytes / 1073741824, 2);
    }
    elseif ($for == 'mb') {
        $bytes = number_format($bytes / 1048576, 2);
    }
    elseif ($for == 'kb') {
        $bytes = number_format($bytes / 1024, 2);
    }
    elseif ($for == 'byte') {
        $bytes = $bytes;
    }
    else {
        $bytes = '0';
    }
    return $bytes;
}
exit();

?>
