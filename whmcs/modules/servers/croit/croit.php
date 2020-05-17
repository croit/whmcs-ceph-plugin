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
* - croit_MetaData()
* - croit_ConfigOptions()
* - croit_CreateAccount()
* - croit_SuspendAccount()
* - croit_UnsuspendAccount()
* - croit_TerminateAccount()
* - croit_ChangePackage()
* - croit_ClientArea()
* Classes list:
*/
if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}
if (file_exists(__DIR__ . '/function.php')) include_once __DIR__ . '/function.php';

function croit_MetaData() {
    return array(
        'DisplayName' => 'croit Provisioning Module',
    );
}

function croit_ConfigOptions() {
    global $whmcs;
    $pid = $whmcs->get_req_var("id");
    $CROIT = new Manage_Croit();
    $CROIT->email_template();
    //$CROIT->croit_CreateConfigOptions($pid);
    $CROIT->croit_CreateCustomFields($pid);
    $croit_GetPlacement_rule = $CROIT->get_placement_admin($pid);
    foreach ($croit_GetPlacement_rule as $value) {
        $place[$value] = $value;
    }

    return array(
        'API_URL' => array(
            'FriendlyName' => 'API URL',
            'Type' => 'text',
            'Size' => '25',
            'Default' => ' ',
            'Description' => 'Enter API URL',
        ) ,
        'Username' => array(
            'FriendlyName' => 'Username',
            'Type' => 'text',
            'Size' => '25',
            'Default' => ' ',
            'Description' => 'Enter username',
        ) ,
        'Password_Field' => array(
            'FriendlyName' => 'Password',
            'Type' => 'password',
            'Size' => '25',
            'Default' => '',
            'Description' => 'Enter secret value here',
        ) ,
        'Default_Bucket_Quota' => array(
            'FriendlyName' => 'Default Bucket Quota',
            'Type' => 'text',
            'Size' => '25',
            'Default' => ' ',
            'Description' => 'Enter Bucket Quota',
        ) ,
        'Default_Bucket_Object' => array(
            'FriendlyName' => 'Default Bucket Object',
            'Type' => 'text',
            'Size' => '25',
            'Default' => '2',
            'Description' => 'Enter Bucket Object',
        ) ,
        'Default_Quota' => array(
            'FriendlyName' => 'Default User Quota',
            'Type' => 'text',
            'Size' => '25',
            'Default' => ' ',
            'Description' => 'Enter Default User Quota',
        ) ,
        'Default_Quota_Object' => array(
            'FriendlyName' => 'Default Quota Object',
            'Type' => 'text',
            'Size' => '25',
            'Default' => '2',
            'Description' => 'Enter Quota Object',
        ) ,
        'Dropdown Field' => array(
            'FriendlyName' => 'Quota Unit',
            'Type' => 'dropdown',
            'Options' => array(
                'kb' => 'KB',
                'mb' => 'MB',
                'gb' => 'GB',
                'tb' => 'TB',
            ) ,
            'Description' => 'Choose one',
        ) ,
        'Placement Field' => array(
            'FriendlyName' => 'Placement rule:',
            'Type' => 'dropdown',
            'Options' => $place,
            'Description' => 'Choose one',
        ) ,
        'uploaded quota limit.' => array(
            'FriendlyName' => 'Uploaded quota limit',
            'Type' => 'text',
            'Size' => '25',
            'Default' => ' ',
            'Description' => 'Enter quota limit in GB',
        ) ,
        'price_per_GB' => array(
            'FriendlyName' => 'Price per GB',
            'Type' => 'text',
            'Size' => '25',
            'Default' => ' ',
            'Description' => 'Enter price per GB',
        ) ,
        'price_per_GB_for_egress' => array(
            'FriendlyName' => 'Price per GB for egress',
            'Type' => 'text',
            'Size' => '25',
            'Default' => ' ',
            'Description' => 'Enter price per GB for egress',
        ) ,

    );
}

function croit_CreateAccount(array $params) {
    try {
        $CROIT = new Manage_Croit();
        $croit_Login = $CROIT->croit_Login($params); #Login with croit
        if (!empty($croit_Login['result']['message'])) return $croit_Login['result']['message'];
        $accessToken = $croit_Login['result']['access_token'];
        $uid = $params['clientsdetails']['firstname'] . $params['serviceid'];
        $email = $params['clientsdetails']['email'];
        $croit_GetUsers = $CROIT->croit_GetUsers($params, $accessToken); #Check User Exist
        if (!empty($croit_GetUsers['result']['message'])) return $croit_GetUsers['result']['message'];
        else {
            foreach ($croit_GetUsers as $user) {
                if ($user['uid'] == $uid) {
                    return "could not create user: unable to create user, user: $uid exists";
                    break;
                }
                elseif ($user['email'] == $email) {
                    return "could not create user: unable to create user, user: $email exists";
                    break;
                }
                continue;
            }
        }
        $croit_CreateUser = $CROIT->croit_CreateUser($params, $accessToken); #Create User
        if (!empty($croit_CreateUser['result']['message'])) return $croit_CreateUser['result']['message'];

        $croit_AssignUserQuota = $CROIT->croit_AssignUserQuota($params, $accessToken, $uid); #Assign User Quota
        if (!empty($croit_AssignUserQuota['result']['message'])) return $croit_AssignUserQuota['result']['message'];

        $croit_AssignBucketQuota = $CROIT->croit_AssignBucketQuota($params, $accessToken, $uid); #Assign Bucket Quota
        if (!empty($croit_AssignBucketQuota['result']['message'])) return $croit_AssignBucketQuota['result']['message'];

        $CROIT->croit_CustomFieldvalue($params, 'croit_uid', $uid);
        $croit_LogOut = $CROIT->croit_LogOut($params);
    }
    catch(Exception $e) {
        logModuleCall('Croit Provisioning Module', __FUNCTION__, $params, $e->getMessage() , $e->getTraceAsString());
        return $e->getMessage();
    }
    return 'success';
}

function croit_SuspendAccount(array $params) {
    $uid = $params['customfields']['croit_uid'];
    $CROIT = new Manage_Croit();
    try {
        if (empty($uid)) return "User id not found.";
        $croit_Login = $CROIT->croit_Login($params); #Login with croit
        if (!empty($croit_Login['result']['message'])) return $croit_Login['result']['message'];
        $accessToken = $croit_Login['result']['access_token'];
        $croit_Suspend = $CROIT->croit_Suspend($params, $accessToken, $uid);
        if (!empty($croit_Suspend['result']['message'])) return $croit_Suspend['result']['message'];
    }
    catch(Exception $e) {
        logModuleCall('Croit Provisioning Module', __FUNCTION__, $params, $e->getMessage() , $e->getTraceAsString());
    }
    return 'success';
}

function croit_UnsuspendAccount(array $params) {
    $uid = $params['customfields']['croit_uid'];
    $CROIT = new Manage_Croit();
    try {
        if (empty($uid)) return "User id not found.";
        $croit_Login = $CROIT->croit_Login($params); #Login with croit
        if (!empty($croit_Login['result']['message'])) return $croit_Login['result']['message'];
        $accessToken = $croit_Login['result']['access_token'];
        $croit_Unsuspend = $CROIT->croit_Unsuspend($params, $accessToken, $uid);

        if (!empty($croit_Unsuspend['result']['message'])) return $croit_Unsuspend['result']['message'];
    }
    catch(Exception $e) {
        logModuleCall('Croit Provisioning Module', __FUNCTION__, $params, $e->getMessage() , $e->getTraceAsString());
    }
    return 'success';
}

function croit_TerminateAccount(array $params) {
    $uid = $params['customfields']['croit_uid'];
    $CROIT = new Manage_Croit();
    try {
        if (empty($uid)) return "User id not found.";
        $croit_Login = $CROIT->croit_Login($params); #Login with croit
        if (!empty($croit_Login['result']['message'])) return $croit_Login['result']['message'];
        $accessToken = $croit_Login['result']['access_token'];
        $croit_Terminate = $CROIT->croit_Terminate($params, $accessToken, $uid);
        if (!empty($croit_Terminate['result']['message'])) return $croit_Terminate['result']['message'];
        $CROIT->croit_CustomFieldvalue($params, 'croit_uid', '');
    }
    catch(Exception $e) {
        logModuleCall('Croit Provisioning Module', __FUNCTION__, $params, $e->getMessage() , $e->getTraceAsString());
    }
    return 'success';
}
function croit_ChangePackage(array $params) {
    $uid = $params['customfields']['croit_uid'];
    $CROIT = new Manage_Croit();
    try {
        if (empty($uid)) return "User id not found.";
        $croit_Login = $CROIT->croit_Login($params); #Login with croit
        if (!empty($croit_Login['result']['message'])) return $croit_Login['result']['message'];
        $accessToken = $croit_Login['result']['access_token'];

        $croit_AssignUserQuota = $CROIT->croit_AssignUserQuota($params, $accessToken, $uid); # update User Quota
        if (!empty($croit_AssignUserQuota['result']['message'])) return $croit_AssignUserQuota['result']['message'];

        $croit_AssignBucketQuota = $CROIT->croit_AssignBucketQuota($params, $accessToken, $uid); # update Bucket Quota
        if (!empty($croit_AssignBucketQuota['result']['message'])) return $croit_AssignBucketQuota['result']['message'];
    }
    catch(Exception $e) {
        logModuleCall('Croit Provisioning Module', __FUNCTION__, $params, $e->getMessage() , $e->getTraceAsString());
    }
    return 'success';
}

function croit_ClientArea(array $params) {

    $CROIT = new Manage_Croit();

    $get_placement_config = $CROIT->get_placement_config();
    $croit_Login = $CROIT->croit_Login($params); #Login with croit
    if (!empty($croit_Login['result']['message'])) return $croit_Login['result']['message'];
    $accessToken = $croit_Login['result']['access_token'];
    $uid = $params['customfields']['croit_uid'];
    $displayname = $params['clientsdetails']['firstname'] . ' ' . $params['clientsdetails']['lastname'];
    $Email = $params['clientsdetails']['email'];
    $user_quota = $params['configoptions']['user_quota'];
    $user_quota_object = $params['configoptions']['user_quota_object'];

    if (isset($_POST['customAction']) && !empty($_POST['customAction'])) {
        include_once __DIR__ . '/ajax/ajax.php';
        exit();
    }
    $croit_LogOut = $CROIT->croit_LogOut($params);

    return array(
        'tabOverviewReplacementTemplate' => 'template/clientarea.tpl',
        'templateVariables' => array(
            'user_id' => $uid,
            'displayname' => $displayname,
            'Email' => $Email,
            'user_quota' => $user_quota,
            'user_quota_object' => $user_quota_object,
            'placement_rule' => $get_placement_config,

        )
    );
}

?>
