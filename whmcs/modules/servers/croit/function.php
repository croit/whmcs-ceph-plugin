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

/**
 * Class and Function List:
 * Function list:
 * - __construct()
 * - croit_CreateConfigOptions()
 * - croit_Login()
 * - croit_LogOut()
 * - croit_GetUsers()
 * - croit_GetUserskey()
 * - croit_CreateUser()
 * - croit_AssignUserQuota()
 * - croit_AssignBucketQuota()
 * - croit_GetUnit()
 * - croit_Suspend()
 * - croit_Unsuspend()
 * - croit_Terminate()
 * - croit_DoRequest()
 * - croit_CreateCustomFields()
 * - croit_GetCustomFieldId()
 * - croit_CustomFieldvalue()
 * - Create_Bucket()
 * - Create_Bucket_only()
 * - croit_GetPlacement_rule()
 * - get_placement_admin()
 * - get_placement_config()
 * - croit_bucket_list()
 * - croit_data_usage()
 * - email_template()
 * Classes list:
 * - Manage_Croit
 */
use WHMCS\Database\Capsule;
class Manage_Croit {
    function __construct() {
    }
    function croit_CreateConfigOptions($pid) {
        $nameExist = Capsule::table('tblproductconfiggroups')->where('name', 'Croit-' . $pid)->first();
        if (empty($nameExist)) {
            try {
                $insertdata = ['name' => 'Croit-' . $pid, ];
                $gid = Capsule::table('tblproductconfiggroups')->insertGetId($insertdata);
            }
            catch(\Exception $e) {
                logActivity("Unable to insert: {$e->getMessage() }");
            }
        }
        else {
            $gid = $nameExist->id;
        }
        if ($gid) {
            if (Capsule::table('tblproductconfiglinks')->where('gid', $gid)->where('pid', $pid)->count() == 0) {
                try {
                    $insertdata = ['gid' => $gid, 'pid' => $pid, ];
                    Capsule::table('tblproductconfiglinks')->insert($insertdata);
                }
                catch(\Exception $e) {
                    logActivity("Unable to insert: {$e->getMessage() }");
                }
            }
        }
        $configOptionArr = ["bucket_quota" => ["optionname" => "bucket_quota|Bucket Quota", "optiontype" => "4", "qtyminimum" => "", "qtymaximum" => "100", ], "bucket_quota_object" => ["optionname" => "bucket_quota_object|Bucket Quota Object", "optiontype" => "4", "qtyminimum" => "", "qtymaximum" => "10", ], "user_quota" => ["optionname" => "user_quota|User Quota", "optiontype" => "4", "qtyminimum" => "", "qtymaximum" => "100", ], "user_quota_object" => ["optionname" => "user_quota_object|User Quota Object", "optiontype" => "4", "qtyminimum" => "", "qtymaximum" => "10", ]];
        foreach ($configOptionArr as $key => $value) {
            $count = Capsule::table('tblproductconfigoptions')->where('gid', $gid)->where('optionname', 'like', '%' . $key . '%')->count();
            if ($count == 0) {
                try {
                    $value['gid'] = $gid;
                    $configId = Capsule::table('tblproductconfigoptions')->insertGetId($value);
                    $optsub = Capsule::table('tblproductconfigoptionssub')->where('configid', $configId)->where('optionname', 'like', '%' . $key . '%')->count();
                    if ($optsub == 0) {
                        $insertData = ['configid' => $configId, 'optionname' => $value['optionname']];
                        $relid = Capsule::table('tblproductconfigoptionssub')->insertGetId($insertData);
                        $currencyData = Capsule::table('tblcurrencies')->get();
                        foreach ($currencyData as $currency) {
                            $count = Capsule::table('tblpricing')->where('relid', $relid)->where('currency', $currency->id)
                                ->where('type', 'configoptions')
                                ->count();
                            if ($count == 0) {
                                try {
                                    $insertdata = ["type" => "configoptions", "currency" => $currency->id, "relid" => $relid];
                                }
                                catch(Exception $ex) {
                                    logActivity("Unable to insert data in tblpricing: {$ex->getMessage() }");
                                }
                            }
                        }
                    }
                }
                catch(Exception $ex) {
                    logActivity("Unable to insert data in tblproductconfigoptions: {$ex->getMessage() }");
                }
            }
        }
    }
    function croit_Login($params) {
        $url = $params['configoption1'] . 'auth/login-form';
        $data = 'username=' . $params['configoption2'] . '&password=' . $params['configoption3'] . '&grant_type=password';
        $result = $this->croit_DoRequest($url, 'POST', $data);
        return $result;
    }
    function croit_LogOut($params) {
        $url = $params['configoption1'] . 'auth/logout';
        $result = $this->croit_DoRequest($url, 'POST');
        return $result;
    }
    function croit_GetUsers($params, $accessToken) {
        $url = $params['configoption1'] . 's3/users';
        $headers = ["Authorization: Bearer {$accessToken} ", "Content-Type: application/json"];
        $result = $this->croit_DoRequest($url, 'GET', '', $headers);
        logModuleCall('Croit Provisioning Module', 'get users', $url, $result);
        return $result;
    }
    function croit_GetUserskey($params, $accessToken, $uid) {
        $url = $params['configoption1'] . 's3/users/' . $uid . '/key';
        $headers = ["Authorization: Bearer {$accessToken} ", "Content-Type: application/json"];
        $result = $this->croit_DoRequest($url, 'GET', '', $headers);
        logModuleCall('Croit Provisioning Module', 'get users key', $url, $result);
        return $result;
    }
    function croit_CreateUser($params, $accessToken) {
        $url = $params['configoption1'] . 's3/users';
        $uid = $params['clientsdetails']['firstname'] . $params['serviceid'];
        $name = $params['clientsdetails']['firstname'];
        $email = $params['clientsdetails']['email'];
        $data = ["uid" => $uid, "name" => $name, "email" => $email, ];
        $headers = ["Authorization: Bearer {$accessToken} ", "Content-Type: application/json"];
        $result = $this->croit_DoRequest($url, 'POST', json_encode($data) , $headers);
        logModuleCall('Croit Provisioning Module', 'create user', $data, $result);
        return $result;
    }
    function croit_AssignUserQuota($params, $accessToken, $uid) {
        $url = $params['configoption1'] . 's3/users/' . $uid . '/quota';
        $maxSizeKb = (empty($params['configoptions']['user_quota'])) ? $params['configoption6'] : $params['configoptions']['user_quota'] + $params['configoption6'];
        $maxObjects = (empty($params['configoptions']['user_quota_object'])) ? $params['configoption7'] : $params['configoptions']['user_quota_object'] + $params['configoption7'];
        $maxSizeKb = $this->croit_GetUnit($maxSizeKb, $params['configoption8']);
        $data = ["enabled" => true, "max_size_kb" => $maxSizeKb, "max_objects" => $maxObjects, ];
        $headers = ["Authorization: Bearer {$accessToken} ", "Content-Type: application/json"];
        if ($maxSizeKb > 0) $result = $this->croit_DoRequest($url, 'PUT', json_encode($data) , $headers);
        else $result = null;
        logModuleCall('Croit Provisioning Module', 'assign user quota', $data, $result);
        return $result;
    }
    function croit_AssignBucketQuota($params, $accessToken, $uid) {
        $url = $params['configoption1'] . 's3/users/' . $uid . '/bucket-quota';
        $maxSizeKb = (empty($params['configoptions']['bucket_quota'])) ? $params['configoption4'] : $params['configoptions']['bucket_quota'] + $params['configoption4'];
        $maxObjects = (empty($params['configoptions']['bucket_quota_object'])) ? $params['configoption5'] : $params['configoptions']['bucket_quota_object'] + $params['configoption5'];
        $maxSizeKb = $this->croit_GetUnit($maxSizeKb, $params['configoption8']);
        $data = ["enabled" => true, "max_size_kb" => $maxSizeKb, "max_objects" => $maxObjects, ];
        $headers = ["Authorization: Bearer {$accessToken}", "Content-Type: application/json"];
        if ($maxSizeKb > 0) $result = $this->croit_DoRequest($url, 'PUT', json_encode($data) , $headers);
        else $result = null;
        logModuleCall('Croit Provisioning Module', 'assign bucket quota', $data, $result);
        return $result;
    }
    private function croit_GetUnit($size, $unit) {
        if ($unit == 'mb') $size = $size * 1000;
        elseif ($unit == 'gb') $size = $size * 1000 * 1000;
        elseif ($unit == 'tb') $size = $size * 1000 * 1000 * 1000;
        return $size;
    }
    function croit_Suspend($params, $accessToken, $uid) {
        $url = $params['configoption1'] . 's3/users/' . $uid;
        $data = ["suspended" => true, ];
        $headers = ["Authorization: Bearer {$accessToken} ", "Content-Type: application/json"];
        $result = $this->croit_DoRequest($url, 'PATCH', json_encode($data) , $headers);
        logModuleCall('Croit Provisioning Module', 'Suspend user', $data, $result);
        return $result;
    }
    function croit_Unsuspend($params, $accessToken, $uid) {
        $url = $params['configoption1'] . 's3/users/' . $uid;
        $data = ["suspended" => false, ];
        $headers = ["Authorization: Bearer {$accessToken} ", "Content-Type: application/json"];
        $result = $this->croit_DoRequest($url, 'PATCH', json_encode($data) , $headers);
        logModuleCall('Croit Provisioning Module', 'Unsuspend user', $data, $result);
        return $result;
    }
    function croit_Terminate($params, $accessToken, $uid) {
        $url = $params['configoption1'] . 's3/users/' . $uid;
        $headers = ["Authorization: Bearer {$accessToken} ", "Content-Type: application/json"];
        $result = $this->croit_DoRequest($url, 'DELETE', json_encode($data) , $headers);
        logModuleCall('Croit Provisioning Module', 'terminate user', $url, $result);
        return $result;
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
    function croit_CreateCustomFields($pid) {
        $Arr = ["croit_uid" => ["fieldname" => "croit_uid|Croit User Id", "type" => "product", "relid" => $pid, "fieldtype" => "text", "showorder" => "", "required" => "", "adminonly" => "on", ]];
        foreach ($Arr as $key => $value) {
            $count = Capsule::table('tblcustomfields')->where('type', 'product')
                ->where('relid', $pid)->where('fieldname', 'like', '%' . $key . '%')->count();
            if ($count == 0) {
                try {
                    Capsule::table('tblcustomfields')->insert($value);
                }
                catch(Exception $ex) {
                    logActivity("Unable to insert data in tblcustomfields: {$ex->getMessage() }");
                }
            }
        }
    }
    function croit_GetCustomFieldId($relid, $fieldname) {
        $data = Capsule::table('tblcustomfields')->where('type', 'product')
            ->where('relid', $relid)->where('fieldname', 'like', '%' . $fieldname . '%')->first();
        return $data->id;
    }
    function croit_CustomFieldvalue($params, $fieldname, $uid = null) {
        $fieldId = $this->croit_GetCustomFieldId($params['pid'], $fieldname);
        $data = ["fieldid" => $fieldId, "relid" => $params['serviceid'], "value" => $uid];
        if (Capsule::table('tblcustomfieldsvalues')->where('relid', $params['serviceid'])->where('fieldid', $fieldId)->count() == 0) {
            try {
                Capsule::table('tblcustomfieldsvalues')
                    ->insert($data);
            }
            catch(Exception $ex) {
                logActivity("Unable to insert data in tblcustomfieldsvalues: {$ex->getMessage() }");
            }
        }
        else {
            try {
                Capsule::table('tblcustomfieldsvalues')
                    ->where('relid', $params['serviceid'])->where('fieldid', $fieldId)->update($data);
            }
            catch(Exception $ex) {
                logActivity("Unable to udpate data in tblcustomfieldsvalues: {$ex->getMessage() }");
            }
        }
    }
    function Create_Bucket($params, $accessToken, $uid, $pid) {
        global $whmcs;
        $croit = Capsule::table('tblproducts')->where('id', $pid)->where('name', 'Croit')
            ->first();
        $placement = $croit->configoption9;
        $postData['owner'] = $uid;
        $postData['placement'] = $whmcs->get_req_var("Placementrule");
        $postData['acl']['grantee'] = 'SINGLE_USER';
        $postData['acl']['userId'] = $uid;
        $postData['acl']['displayName'] = $whmcs->get_req_var("name");
        $postData['acl']['permission'] = $whmcs->get_req_var("permission");
        $statusQuota['enabled'] = $whmcs->get_req_var("enable");
        $postDataQuota['enabled'] = true;
        $bucketSizeKb = $whmcs->get_req_var("addbucketquota");
        $postDataQuota['max_size_kb'] = $this->croit_GetUnit($bucketSizeKb, $whmcs->get_req_var("unit"));
        $postDataQuota['max_objects'] = $whmcs->get_req_var("Bucketobject");
        if (empty($postData['acl']['displayName'])) {
            $msg = '<div class="alert alert-danger">
					  <strong>Error! </strong> Name is required! 
					</div>';
            echo $msg;
            die();
        }
        elseif (empty($postData['acl']['permission'])) {
            $msg = '<div class="alert alert-danger">
					  <strong>Error!</strong> Permission is required!. 
					</div>';
            echo $msg;
            die();
        }
        elseif ($statusQuota['enabled'] == 'enable') {
            $postDataEncoded = json_encode($postData);
            $headers = ["Authorization: Bearer {$accessToken} ", "Content-Type: application/json"];
            $BucketquotaURL = $params['configoption1'] . 's3/buckets/' . $whmcs->get_req_var("name");
            $Create_Bucket_name = $this->croit_DoRequest($BucketquotaURL, 'PUT', $postDataEncoded, $headers);
            logModuleCall('Croit Provisioning Module', ' add bucket ', $postDataEncoded, $Create_Bucket_name);
            if ($Create_Bucket_name['code'] != 200 && $Create_Bucket_name['result']['message'] != '') {
                $msg = '<div class="alert alert-danger">
						<strong>Error!</strong> ' . $Create_Bucket_name['result']['message'] . '
						</div>';
                echo $msg;
                die();
            }
            $postDataQuotaEncoded = json_encode($postDataQuota);
            $AddBucketquotaUrl = $params['configoption1'] . 's3/buckets/' . $whmcs->get_req_var("name") . '/quota';
            $Add_Bucket_Quota = $this->croit_DoRequest($AddBucketquotaUrl, 'PUT', $postDataQuotaEncoded, $headers);
            logModuleCall('Croit Provisioning Module', ' add bucket quota ', $postDataQuotaEncoded, $Add_Bucket_Quota);
            $msg = '<div class="alert alert-success">
						  <strong>Success!</strong> Bucket quota has been succesfully created.
						</div>';
            echo $msg;
            die();
        }
        else {
            $postDataEncoded = json_encode($postData);
            $headers = ["Authorization: Bearer {$accessToken} ", "Content-Type: application/json"];
            $BucketquotaURL = $params['configoption1'] . 's3/buckets/' . $whmcs->get_req_var("name");
            $Create_Bucket_name = $this->croit_DoRequest($BucketquotaURL, 'PUT', $postDataEncoded, $headers);
            logModuleCall('Croit Provisioning Module', ' add bucket ', $postDataEncoded, $Create_Bucket_name);
            if ($Create_Bucket_name['code'] != 200 && $Create_Bucket_name['result']['message'] != '') {
                $msg = '<div class="alert alert-danger">

					<strong>Error!</strong> ' . $Create_Bucket_name['result']['message'] . '

					</div>';
                echo $msg;
                die();
            }
            else {
                if ($statusQuota['enabled'] == "enable") {
                    $postDataQuotaEncoded = json_encode($postDataQuota);
                    $AddBucketquotaUrl = $params['configoption1'] . 's3/buckets/' . $whmcs->get_req_var("name") . '/quota';
                    $Add_Bucket_Quota = $this->croit_DoRequest($AddBucketquotaUrl, 'PUT', $postDataQuotaEncoded, $headers);
                    logModuleCall('Croit Provisioning Module', ' add bucket quota ', $postDataQuotaEncoded, $Add_Bucket_Quota);
                }
                $msg = '<div class="alert alert-success">

						  <strong>Success!</strong> Bucket has been succesfully created.

						</div>';
                echo $msg;
                die();
            }
            $croit_LogOut = $CROIT->croit_LogOut($params);
            echo json_encode($Create_Bucket_name);
            die();
        }
    }
    function Create_Bucket_only($params, $accessToken, $uid) {
        global $whmcs;
        $name = $whmcs->get_req_var("bucketname");
        $postDataQuota['enabled'] = true;
        $bucketSizeKb = $whmcs->get_req_var("addbucketquota1");
        $postDataQuota['max_size_kb'] = $this->croit_GetUnit($bucketSizeKb, $whmcs->get_req_var("unit"));
        $postDataQuota['max_objects'] = $whmcs->get_req_var("Bucketobject1");
        $headers = ["Authorization: Bearer {$accessToken} ", "Content-Type: application/json"];
        $postDataQuotaEncoded = json_encode($postDataQuota);
        $AddBucketquotaUrl = $params['configoption1'] . 's3/buckets/' . $name . '/quota';
        $Add_Bucket_Quota = $this->croit_DoRequest($AddBucketquotaUrl, 'PUT', $postDataQuotaEncoded, $headers);
        logModuleCall('Croit Provisioning Module', ' add bucket quota only ', $postDataQuotaEncoded, $Add_Bucket_Quota);
        if ($Create_Bucket_name['code'] != 200 && $Create_Bucket_name['result']['message'] != '') {
            $msg = '<div class="alert alert-danger">
						<strong>Error!</strong> ' . $Create_Bucket_name['result']['message'] . '
						</div>';
            echo $msg;
            die();
        }
        else {
            $msg = '<div class="alert alert-success">
							  <strong>Success!</strong> Bucket quota has been succesfully Updated.
							</div>';
            echo $msg;
            die();
        }
    }
    function croit_GetPlacement_rule($params, $accessToken, $uid) {
        $url = $params['configoption1'] . 's3/placements';
        $headers = ["Authorization: Bearer {$accessToken} ", "Content-Type: application/json"];
        $result = $this->croit_DoRequest($url, 'GET', '', $headers);
        logModuleCall('Croit Provisioning Module', 'get placement rule ', $url, $result);
        return $result;
    }
    function get_placement_admin($pid) {
        $croit = Capsule::table('tblproducts')->where('id', $pid)->where('name', 'Croit')
            ->first();
        $url = $croit->configoption1 . 'auth/login-form';
        $username = $croit->configoption2;
        $password = $croit->configoption3;
        $data = 'username=' . $username . '&password=' . $password . '&grant_type=password';
        $result = $this->croit_DoRequest($url, 'POST', $data);
        $accessToken = $result['result']['access_token'];
        $urlp = $croit->configoption1 . 's3/placements';
        $headers = ["Authorization: Bearer {$accessToken} ", "Content-Type: application/json"];
        $result_placement = $this->croit_DoRequest($urlp, 'GET', '', $headers);
        logModuleCall('Croit Provisioning Module', 'get placement rule admin ', $url, $result);
        foreach ($result_placement as $key => $value) {
            foreach ($value as $placement) {
                $data1[] = $placement['name'];
            }
        }
        return $data1;
    }
    function get_placement_config() {
        $get = Capsule::table('tblproducts')->where('name', 'Croit')
            ->first();
        logModuleCall('Croit Provisioning Module', 'get placement rule Client area', 'database', $get);
        $placementrule = $get->configoption9;
        return $placementrule;
    }
    function croit_bucket_list($params, $accessToken, $uid) {
        $url = $params['configoption1'] . 's3/buckets';
        $headers = ["Authorization: Bearer {$accessToken} ", "Content-Type: application/json"];
        $result = $this->croit_DoRequest($url, 'GET', '', $headers);
        logModuleCall('Croit Provisioning Module', 'get Bucket list ', $url, $result);
        return $result;
    }
    function croit_data_usage($params, $accessToken, $uid, $bktname) {
        $url = $params['configoption1'] . 's3/usage/by-bucket/' . $bktname;
        $headers = ["Authorization: Bearer {$accessToken} ", "Content-Type: application/json"];
        $result = $this->croit_DoRequest($url, 'GET', '', $headers);
        logModuleCall('Croit Provisioning Module', 'get Data usage by bucket ', $url, $result);
        return $result;
    }
    function email_template() {
        $exist = Capsule::table('tblemailtemplates')->where('type', 'general')
            ->where('name', 'Low credit balance')
            ->count();
        if ($exist == 0) {
            $insertdata = ['type' => 'general', 'name' => 'Low credit balance', 'subject' => 'Low Credit Reminder', 'message' => '<p>Hello <b>{$client_name}<b>,</p><p>
                                This is a reminder that your credits balance ({$avil_credits}) is low. Please refill your credit balance.
                                </p>', 'custom' => '1', ];
            Capsule::table('tblemailtemplates')->insert($insertdata);
        }
    }
}
?>
