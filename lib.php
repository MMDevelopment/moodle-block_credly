<?php

/**
 * Helper functions for integrating with Credly Open Credit API
 *
 * @package    block_credly
 * @copyright  2014 Deds Castillo, MM Development Services (http://mmmoodle.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

function block_credly_api_endpoint() {
    return 'https://api.credly.com/v1.1/';
}

function block_credly_call_api($url="", $method='GET', $data=array()) {
    global $CFG;

    $apikey = trim(get_config('block_credly', 'apikey'));
    $apisecret = trim(get_config('block_credly', 'apisecret'));
    $apiuser = trim(get_config('block_credly', 'apiuser'));
    $apipassword = trim(get_config('block_credly', 'apipassword'));

    if (empty($apikey) || empty($apisecret) || empty($apiuser) || empty($apipassword)) {
        $context = context_system::instance();
        if (has_capability('block/credly:managebadge', $context)) {
            $configureurl = $CFG->wwwroot.'/admin/settings.php?section=blocksettingcredly';
            throw new Exception(get_string('notconfigured', 'block_credly', array('configureurl'=>$configureurl)));
        } else {
            return false;
        }
    }
    $http_info = array();
    $ci = curl_init();
    if (defined("CURL_CA_BUNDLE_PATH")) curl_setopt($ch, CURLOPT_CAINFO, CURL_CA_BUNDLE_PATH);
    curl_setopt($ci, CURLOPT_CONNECTTIMEOUT, 60);
    curl_setopt($ci, CURLOPT_TIMEOUT, 60);
    curl_setopt($ci, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($ci, CURLOPT_SSL_VERIFYPEER, FALSE);
    $http_headers = array(
        'X-Api-Key: '.$apikey,
        'X-Api-Secret: '.$apisecret,
    );
    curl_setopt($ci, CURLOPT_HTTPHEADER, $http_headers);

    switch ($method) {
        case 'POST':
            curl_setopt($ci, CURLOPT_CUSTOMREQUEST, 'POST');
            if (!empty($data)) {
                curl_setopt($ci, CURLOPT_POSTFIELDS, $data);
            }
            break;
        case 'PUT':
            curl_setopt($ci, CURLOPT_CUSTOMREQUEST, 'PUT');
            break;
        case 'DELETE':
            curl_setopt($ci, CURLOPT_CUSTOMREQUEST, 'DELETE');
            if (!empty($data)) {
                $url = sprintf('%s?%s', $url, http_build_query($data));
            }
            break;
        default:
            if (!empty($data)) {
                $url = sprintf('%s?%s', $url, http_build_query($data));
            }
            break;
    }
    if (strpos($url, 'authenticate') !== FALSE) {
        curl_setopt($ci, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($ci, CURLOPT_USERPWD, $apiuser.":".$apipassword);
    }

    curl_setopt($ci, CURLOPT_URL, $url);
    $response = curl_exec($ci);

    if ($response) {
        $http_code = curl_getinfo($ci, CURLINFO_HTTP_CODE);
        $http_info = curl_getinfo($ci);
    }

    curl_close ($ci);
    return $response;
}

function block_credly_get_from_cache($userid=0, $itemtype) {
    global $DB;
    $rec = $DB->get_record('block_credly_cache',array('userid'=>$userid, 'itemtype'=>$itemtype));
    return $rec;
}

function block_credly_delete_from_cache($userid=0, $itemtype) {
    global $DB;
    $DB->delete_records('block_credly_cache', array('userid'=>$userid, 'itemtype'=>$itemtype));
    return $rec;
}

function block_credly_create_cache_entry($userid=0, $itemtype=NULL, $value='', $timeexpires=NULL) {
    global $DB;
    if ($timeexpires === NULL) {
        $timeexpires = time() + (60 * 60 * 24);
    }

    if (!empty($itemtype)) {
        $rec = new stdClass();
        $rec->userid = $userid;
        $rec->itemtype = $itemtype;
        $rec->value = $value;
        $rec->timeexpires = $timeexpires;
        $recid = $DB->insert_record('block_credly_cache', $rec);
        return $recid;
    }
    return false;
}

function block_credly_get_token() {
    $tokenrec = block_credly_get_from_cache(0, 'token');
    if ($tokenrec) {
        return $tokenrec->value;
    } else {
        $result_json = block_credly_call_api(block_credly_api_endpoint().'authenticate','POST');
        if ($result_json !== FALSE) {
            $result = json_decode($result_json);
            if (isset($result->meta) && $result->meta->status_code == '200' && $result->meta->status == 'OK') {
                $timeexpires =  time() + (60 * 60 * 24 * 7);

                block_credly_create_cache_entry(0, 'token', $result->data->token, $timeexpires);
                block_credly_create_cache_entry(0, 'refresh_token', $result->data->refresh_token, $timeexpires);

                return $result->data->token;
            }
        }
    }

    return false;
}

function block_credly_get_admin_member_id() {
    global $DB;

    if ($memberinfo = block_credly_get_from_cache(0, 'adminmemberid')) {
        return $memberinfo->value;
    }

    $token = block_credly_get_token();
    if ($token !== FALSE) {
        $email = trim(get_config('block_credly', 'apiuser'));
        $data = array(
            'email'=>$email,
            'has_profile'=>0,
            'verbose'=>0,
            'page'=>1,
            'per_page'=>1,
            'order_direction'=>'ASC',
            'access_token'=>$token,
        );
        $result_json = block_credly_call_api(block_credly_api_endpoint().'members', 'GET', $data);
        $result = json_decode($result_json);
        if (isset($result->meta) && $result->meta->status_code == '200' && $result->meta->status == 'OK') {
            $timeexpires =  time() + (60 * 60 * 24 * 90);
            block_credly_create_cache_entry(0, 'adminmemberid', $result->data[0]->id, $timeexpires);
            return $result->data[0]->id;
        }
    }
    return NULL;
}

function block_credly_get_member_id($userid=NULL) {
    global $DB;

    if (empty($userid)) {
        global $USER;
        if (empty($USER->id)) {
            return NULL;
        } else {
            $userid = $USER->id;
        }
    }

    if ($memberinfo = block_credly_get_from_cache($userid, 'memberid')) {
        return $memberinfo->value;
    }

    $token = block_credly_get_token();
    if ($token !== FALSE) {
        $mdluser = $DB->get_record('user', array('id'=>$userid));
        if ($mdluser) {
            $email = $mdluser->email;
            $data = array(
                'email'=>$email,
                'has_profile'=>0,
                'verbose'=>0,
                'page'=>1,
                'per_page'=>1,
                'order_direction'=>'ASC',
                'access_token'=>$token,
            );
            $result_json = block_credly_call_api(block_credly_api_endpoint().'members', 'GET', $data);
            $result = json_decode($result_json);
            if (isset($result->meta) && $result->meta->status_code == '200' && $result->meta->status == 'OK') {
                $timeexpires =  time() + (60 * 60 * 24 * 30);
                block_credly_create_cache_entry($userid, 'memberid', $result->data[0]->id, $timeexpires);
                return $result->data[0]->id;
            }
        }
    }
    return NULL;
}

function block_credly_get_member_badges($userid=NULL) {
    global $DB;

    if (empty($userid)) {
        global $USER;
        if (empty($USER->id)) {
            return NULL;
        } else {
            $userid = $USER->id;
        }
    }

    $memberid = block_credly_get_member_id($userid);
    if ($memberid) {
        if ($memberbadges = block_credly_get_from_cache($userid, 'memberbadges')) {
            return json_decode($memberbadges->value);
        }

        $token = block_credly_get_token();
        if ($token !== FALSE) {
            $data = array(
                'page'=>1,
                'per_page'=>1000,
                'order_direction'=>'ASC',
                'access_token'=>$token,
            );
            $result_json = block_credly_call_api(block_credly_api_endpoint().'members/'.$memberid.'/badges', 'GET', $data);
            $result = json_decode($result_json);
            if (isset($result->meta) && $result->meta->status_code == '200' && $result->meta->status == 'OK') {
                $timeexpires =  time() + (60 * 60);
                $badges = $result->data;
                block_credly_create_cache_entry($userid, 'memberbadges', json_encode($badges), $timeexpires);
                return $badges;
            }
        }
    }
    return NULL;
}

function block_credly_get_admin_created_badges() {
    global $DB;

    $memberid = block_credly_get_admin_member_id();

    if ($memberid) {
        if ($memberbadges = block_credly_get_from_cache(0, 'admincreatedbadges')) {
            return json_decode($memberbadges->value);
        }

        $token = block_credly_get_token();
        if ($token !== FALSE) {
            $data = array(
                'page'=>1,
                'per_page'=>1000,
                'order_direction'=>'ASC',
            );
            $result_json = block_credly_call_api(block_credly_api_endpoint().'members/'.$memberid.'/badges/created', 'GET', $data);
            $result = json_decode($result_json);
            if (isset($result->meta) && $result->meta->status_code == '200' && $result->meta->status == 'OK') {
                $timeexpires =  time() + (60 * 60 * 24);
                $badges = $result->data;
                block_credly_create_cache_entry(0, 'admincreatedbadges', json_encode($badges), $timeexpires);
                return $badges;
            }
        }
    }
    return NULL;
}

function block_credly_get_badge_info($id) {
    global $DB;

    $token = block_credly_get_token();
    if ($token !== FALSE) {
        $data = array(
            'id'=>$id,
            'verbose'=>0,
        );
        $result_json = block_credly_call_api(block_credly_api_endpoint().'badges/'.$id, 'GET', $data);
        $result = json_decode($result_json);
        if (isset($result->meta) && $result->meta->status_code == '200' && $result->meta->status == 'OK') {
            $badges = $result->data;
            return $badges;
        }
    }
    return NULL;
}

function block_credly_create_badge($formdata, $image_data) {
    $token = block_credly_get_token();
    if ($token !== FALSE) {
        $data = array(
            'attachment'=>base64_encode($image_data),
            'title'=>$formdata->title,
            'short_description'=>$formdata->short_description,
            'description'=>$formdata->description,
            'criteria'=>$formdata->criteria,
            'is_giveable'=>$formdata->is_giveable,
            'expires_in'=>$formdata->expires_in,
            'access_token'=>$token,
        );
        $result_json = block_credly_call_api(block_credly_api_endpoint().'badges', 'POST', $data);
        $result = json_decode($result_json);
        if (isset($result->meta) && $result->meta->status_code == '200' && $result->meta->status == 'OK') {
            block_credly_delete_from_cache(0, 'admincreatedbadges');
            $badgeid = $result->data;
            return $badgeid;
        } else {
            print_error('credlyerrorresponse', 'block_credly', '', '('.$result->meta->status.') '.$result->meta->message);
        }
    }
}

function block_credly_update_badge($id=NULL, $formdata, $image_data=NULL) {
    if ($id) {
        $token = block_credly_get_token();
        if ($token !== FALSE) {
            $data = array(
                'title'=>$formdata->title,
                'short_description'=>$formdata->short_description,
                'description'=>$formdata->description,
                'criteria'=>$formdata->criteria,
                'is_giveable'=>$formdata->is_giveable,
                'expires_in'=>$formdata->expires_in,
                'access_token'=>$token,
            );
            if (!empty($image_data)) {
                $data['attachment']=base64_encode($image_data);
            }
            $result_json = block_credly_call_api(block_credly_api_endpoint().'badges/'.$id, 'POST', $data);
            $result = json_decode($result_json);
            if (isset($result->meta) && $result->meta->status_code == '200' && $result->meta->status == 'OK') {
                block_credly_delete_from_cache(0, 'admincreatedbadges');
                $badgeid = $result->data;
                return $badgeid;
            } else {
                print_error('credlyerrorresponse', 'block_credly', '', '('.$result->meta->status.') '.$result->meta->message);
            }
        }
    }
}

function block_credly_grant_badge($userid, $badgeid) {
    global $DB;

    if (!empty($userid)) {
        $token = block_credly_get_token();
        if ($token !== FALSE) {
            $mdluser = $DB->get_record('user', array('id'=>$userid));
            if ($mdluser) {
                $data = array(
                    'email'=>$mdluser->email,
                    'first_name'=>$mdluser->firstname,
                    'last_name'=>$mdluser->lastname,
                    'badge_id'=>$badgeid,
                    'notify'=>1,
                    'access_token'=>$token,
                );
                $result_json = block_credly_call_api(block_credly_api_endpoint().'member_badges', 'POST', $data);
                $result = json_decode($result_json);
                if (isset($result->meta) && $result->meta->status_code == '200' && $result->meta->status == 'OK') {
                    block_credly_delete_from_cache($userid, 'memberbadges');
                    return $result->data;
                }
            }
        }
    }
    return NULL;
}

