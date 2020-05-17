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
* - formatSizeUnits()
* - formatSizeUnitsinGB()
* Classes list:
*/
global $whmcs;
use WHMCS\Database\Capsule;
$customAction = $whmcs->get_req_var("customAction");
if ($customAction == "uid") {
    $croit_GetUsers = $CROIT->croit_GetUsers($params, $accessToken);
    logModuleCall('Croit Provisioning Module', ' get user detail', $data, $croit_GetUsers);
    $croit_LogOut = $CROIT->croit_LogOut($params);
    if ($croit_GetUsers[code] == 200) {
        foreach ($croit_GetUsers['result'] as $value) {
            if ($value['uid'] == $uid) {
                $bucketquato = formatSizeUnits($value['userQuota']['max_size_kb']);
                $value['userQuota']['max_size_kb'] = $bucketquato;
                print json_encode($value);
                die();
            }
        }
    }
}
function formatSizeUnits($bytes) {
    if ($bytes >= 1000000000) {
        $bytes = number_format($bytes / 1000000000, 2) . ' TB';
    }
    elseif ($bytes >= 1000000) {
        $bytes = number_format($bytes / 1000000, 2) . ' GB';
    }
    elseif ($bytes >= 1000) {
        $bytes = number_format($bytes / 1000, 2) . ' MB';
    }
    elseif ($bytes < 1000) {
        $bytes = $bytes . ' KB';
    }
    return $bytes;
}
if ($customAction == "update") {
    $url = $params['configoption1'] . 's3/users/' . $uid;
    $displayname = $whmcs->get_req_var("displayname");
    $userUpdated_email = $whmcs->get_req_var("email");
    $croit_GetUsers = $CROIT->croit_GetUsers($params, $accessToken); #Check User Exist
    logModuleCall('Croit Provisioning Module', ' get user detail', $data, $croit_GetUsers);
    if (!empty($croit_GetUsers['result']['message'])) {
        $msg = '<div class="alert alert-danger">

				  <strong>Error!</strong> ' . $croit_GetUsers['result']['message'] . '

				</div>';
        echo $msg;
        die();
    }
    else {
        foreach ($croit_GetUsers['result'] as $user) {
            if ($user['uid'] == $uid) {
                continue;
            }
            elseif ($user['email'] == $email && $user['uid'] != $uid) {
                $msg = '<div class="alert alert-danger">

				  <strong>Error!</strong> could not update user: unable to update user, user: ' . $email . ' exists

				</div>';
                echo $msg;
                die();
                break;
            }
            continue;
        }
    }
    $data = ["name" => $displayname, "email" => $userUpdated_email, ];
    $headers = ["Authorization: Bearer {$accessToken} ", "Content-Type: application/json"];
    $result = $CROIT->croit_DoRequest($url, 'PATCH', json_encode($data) , $headers);
    logModuleCall('Croit Provisioning Module', 'Update user Info', $data, $result);
    $croit_LogOut = $CROIT->croit_LogOut($params);
    if (!empty($result['result']['message'])) {
        $msg = '<div class="alert alert-danger">

				  <strong>Error!</strong> ' . $result['result']['message'] . '

				</div>';
        echo $msg;
        die();
    }
    else {
        $msg = '<div class="alert alert-success">

				  <strong>Success!</strong> Data has been succesfully updated.

				</div>';
        echo $msg;
        die();
    }
}
if ($customAction == "subuser") {
    $url = $params['configoption1'] . 's3/users/' . $uid;
    $permission = $whmcs->get_req_var("permission");
    $addsubuser = $whmcs->get_req_var("addsubuser");
    $croit_GetUsers = $CROIT->croit_GetUsers($params, $accessToken); #Check subUser Exist
    logModuleCall('Croit Provisioning Module', ' add Subuser', $data, $croit_GetUsers);
    if (!empty($croit_GetUsers['result']['message'])) {
        $msg = '<div class="alert alert-danger">

				  <strong>Error!</strong> ' . $croit_GetUsers['result']['message'] . '

				</div>';
        echo $msg;
        die();
    }
    else {
        foreach ($croit_GetUsers['result'] as $user) {
            foreach ($user['subusers'] as $subusersvalue) {
                if ($subusersvalue['subuser'] == $addsubuser) {
                    $msg = '<div class="alert alert-danger">

				  <strong>Error!</strong> could not Add Subuser: unable to Add Subuser, user: ' . $addsubuser . ' exists

				</div>';
                    echo $msg;
                    die();
                    break;
                }
                else {
                    $permission[subuser] = $subusersvalue['subuser'];
                    $permission[permission] = $subusersvalue['permission'];
                }
                continue;
            }
            continue;
        }
        echo json_encode($permission);
        echo json_encode($addsubuser);
        die;
    }
    $data = ["subuser" => $addsubuser, "permissions" => $permission, ];
    $headers = ["Authorization: Bearer {$accessToken} ", "Content-Type: application/json"];
    $result = $CROIT->croit_DoRequest($url, 'POST', json_encode($data) , $headers);
    logModuleCall('Croit Provisioning Module', 'add Subuser', $data, $result);
    $croit_LogOut = $CROIT->croit_LogOut($params);
}
if ($customAction == "getkeys") {
    $croit_GetUserskey = $CROIT->croit_GetUserskey($params, $accessToken, $uid); #Check Userkey
    logModuleCall('Croit Provisioning Module', ' get user key', $data, $croit_GetUserskey);
    $response = '';
    if (!empty($croit_GetUserskey['result']['message'])) $response = ['status' => 'error', 'msg' => $croit_GetUserskey['result']['message']];
    else $response = ['status' => 'success', "user" => $croit_GetUserskey['result']['user'], "access_key" => $croit_GetUserskey['result']['access_key'], "secret_key" => $croit_GetUserskey['result']['secret_key']];
    print json_encode($response);
    die;
}
if ($customAction == "bucketquotainfo") {
    $croit_bucket_list = $CROIT->croit_bucket_list($params, $accessToken, $uid);
    logModuleCall('Croit Provisioning Module', ' bucket list ', $data, $croit_bucket_list);
    $html = '';
    if ($croit_bucket_list['code'] == 200) {
        foreach ($croit_bucket_list['result'] as $value) {
            if ($value['owner'] == $uid) {
                $bktobj = $value['bucket_quota']['max_objects'];
                $enabled = $value['bucket_quota']['enabled'];
                $bucketquato = formatSizeUnits($value['bucket_quota']['max_size_kb']);
                $last_space = strrpos($bucketquato, ' ');
                $units = substr($bucketquato, $last_space);
                $unitonly = strtolower($units);
                $unitdata = substr($bucketquato, 0, $last_space);
                if ($enabled == '') {
                    $value['bucket_quota']['max_size_kb'] = $bucketquato;
                    $html .= '<tr><td>' . $value['bucket'] . '</td><td>' . '-' . '</td><td>' . '-' . '</td><td><button class="btn btn-primary data-toggle="modal"   onclick="bktadd(\'' . $value['bucket'] . '\',\'' . $unitdata . '\',\'' . $bktobj . '\',\'' . $unitonly . '\' );">Update Quota</button><button class="btn btn-success"style="margin-left:2px;" onclick="bktusage(\'' . $value['bucket'] . '\' );">Get  stats</button></td></tr>';
                }
                elseif ($value['bucket_quota']['max_size_kb'] == 0 && $value['bucket_quota']['max_objects'] == - 1) {
                    $value['bucket_quota']['max_size_kb'] = $bucketquato;
                    $html .= '<tr><td>' . $value['bucket'] . '</td><td>' . '∞' . '</td><td>' . '∞' . '</td><td><button class="btn btn-primary data-toggle="modal"   onclick="bktadd(\'' . $value['bucket'] . '\',\'' . $unitdata . '\',\'' . $bktobj . '\',\'' . $unitonly . '\' );">Update Quota</button><button class="btn btn-success"style="margin-left:2px;" onclick="bktusage(\'' . $value['bucket'] . '\' );">Get  stats</button></td></tr>';
                }
                else {
                    $value['bucket_quota']['max_size_kb'] = $bucketquato;
                    $html .= '<tr><td>' . $value['bucket'] . '</td><td>' . $value['bucket_quota']['max_size_kb'] . '</td><td>' . $value['bucket_quota']['max_objects'] . '</td><td><button class="btn btn-primary data-toggle="modal"   onclick="bktadd(\'' . $value['bucket'] . '\',\'' . $unitdata . '\',\'' . $bktobj . '\',\'' . $unitonly . '\' );">Update Quota</button><button class="btn btn-success"   style="margin-left:2px;" onclick="bktusage(\'' . $value['bucket'] . '\' );">Get  stats</button></td ></tr>';
                }
            }
        }
    }
    else {
        $html = '<tr><td colspan="100%" style="color:#ff0000;">' . $croit_bucket_list['result']['message'] . '</td></tr>';
    }
    echo $html;
    exit;
}
if ($customAction == "AddBucket") {
    $croit_Create_Bucket = $CROIT->Create_Bucket($params, $accessToken, $uid, $pid);
    logModuleCall('Croit Provisioning Module', ' create bucket ', $data, $croit_Create_Bucket);
    echo json_encode($croit_Create_Bucket['result']);
    die();
}
if ($customAction == "AddBucketonly") {
    $croit_Create_Bucket = $CROIT->Create_Bucket_only($params, $accessToken, $uid);
    echo '';
    logModuleCall('Croit Provisioning Module', ' create bucket only ', $data, $croit_Create_Bucket);
    echo json_encode($croit_Create_Bucket['result']);
    die();
}
function formatSizeUnitsinGB($bytes) {
    $bytes = number_format($bytes / 1073741824, 2);
    return $bytes;
}
if ($_REQUEST['customAction'] == "bucketdatainfo") {
    $bktname = $_REQUEST['bucketname'];
    $croit_data = $CROIT->croit_data_usage($params, $accessToken, $uid, $bktname);
    $uid = $params[clientsdetails][userid];
    $getcid = Capsule::table('tblclients')->where('id', $uid)->first();
    echo "<pre>";
    $cid = $getcid->currency;
    $currency = Capsule::table('tblcurrencies')->where('id', $uid)->first();
    $rate = $currency->rate;
    $prefix = $currency->prefix;
    $suffix = $currency->suffix;
    $limit = $params[configoption10];
    $charges = $params[configoption11];
    logModuleCall('Croit Provisioning Module', ' croit data usage by bucket n', $data, $croit_data);
    if ($croit_data['code'] == 200) {
        if (!empty($croit_data['result']['buckets'])) {
            foreach ($croit_data['result']['buckets'] as $bucketData) {
                foreach ($bucketData as $oneBucketData) {
                    $bucketName = $oneBucketData['bucketName'];
                    $time = $oneBucketData['time'];
                    $bucketdate = date('d M Y', $time);
                    $currentdate = date("d M Y");
                    $bucketDownloadData = formatSizeUnitsinGB($oneBucketData['bytesDownloaded']);
                    $bucketUploadData = formatSizeUnitsinGB($oneBucketData['bytesUploaded']);
                    //    $bucketDownloadData = '2';
                    //    $bucketUploadData = '3';
                    $datausage = $bucketDownloadData + $bucketUploadData;
                    if ($datausage > $limit) {
                        $datausage = $datausage - $limit;
                        $datacharges = ($datausage * $charges) * $rate;
                    }
                    else {
                        $datacharges = '0.00';
                    }
                    $htmlusage = '<div><h5>bucket :  ' . $bucketName . '</h5><h5> Data usage from :  ' . $bucketdate . '  -  ' . $currentdate . '</h5><h5> Data usage charges :  ' . $prefix . ' ' . $datacharges . $suffix . '</h5></div><div id="container" style="min-width: 310px; height: 400px; margin: 0 auto"></div><table id="datatable" class="table " cellspacing="1"><thead><tr> <th > In</th><th>Dowanload   </th><th>Upload</th></tr></thead><tbody><tr><td >GB</td><td>' . $bucketDownloadData . '</td><td>' . $bucketUploadData . '</td></tr></tbody></table>';
                    echo "<script language='javascript'>
                         Highcharts.chart('container', {
                            plotOptions: {
                                    series: {
                                        pointWidth: 50    
                                    }
                                },
                              data: {
                                table: 'datatable'
                              },
                              chart: {
                                type: 'column'
                              },
                              title: {
                                text: 'Data Usage '
                              },
                              yAxis: {
                                allowDecimals: false,
                                title: {
                                  text: 'Data usage in GB'
                                }
                              },
                              tooltip: {
                                formatter: function () {
                                  return '<b>' + this.series.name + '</b><br/>' +
                                    this.point.y + ' ' + this.point.name.toLowerCase();
                                }
                              }
                            });
                    </script>
                    ";
                    echo $htmlusage;
                }
            }
        }
    }
    die();
}
?>
<script type="text/javascript" src="js/client.js"></script>

