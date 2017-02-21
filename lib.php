<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Helper functions for integrating with Credly Open Credit API
 *
 * @package    block_credly
 * @copyright  2014-2017 Deds Castillo, MM Development Services (http://michaelmino.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * The Credly Open Credit API endpoint URL
 *
 * @return string the endpoint url
 */
function block_credly_api_endpoint() {
    return 'https://api.credly.com/v1.1/';
}

/**
 * Perform a request to the Credly Open Credit API
 * through curl
 *
 * @param string $url the full url
 * @param string $method the http method to use
 * @param array $data an associative area of variables to include in the request
 * @return string the endpoint url
 */
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
            throw new Exception(get_string('notconfigured', 'block_credly', array('configureurl' => $configureurl)));
        } else {
            return false;
        }
    }
    $httpinfo = array();
    $ci = curl_init();
    if (defined("CURL_CA_BUNDLE_PATH")) {
        curl_setopt($ch, CURLOPT_CAINFO, CURL_CA_BUNDLE_PATH);
    }
    curl_setopt($ci, CURLOPT_CONNECTTIMEOUT, 60);
    curl_setopt($ci, CURLOPT_TIMEOUT, 60);
    curl_setopt($ci, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ci, CURLOPT_SSL_VERIFYPEER, false);
    $httpheaders = array(
        'X-Api-Key: '.$apikey,
        'X-Api-Secret: '.$apisecret,
    );
    curl_setopt($ci, CURLOPT_HTTPHEADER, $httpheaders);

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
    if (strpos($url, 'authenticate') !== false) {
        curl_setopt($ci, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($ci, CURLOPT_USERPWD, $apiuser.":".$apipassword);
    }

    curl_setopt($ci, CURLOPT_URL, $url);
    $response = curl_exec($ci);

    if ($response) {
        $httpcode = curl_getinfo($ci, CURLINFO_HTTP_CODE);
        $httpinfo = curl_getinfo($ci);
    }

    curl_close ($ci);
    return $response;
}

/**
 * Checks for a credly-specific cached value in the database
 *
 * @param int $userid the user id the variable is tied to
 * @param string $itemtype the item type of the variable
 * @return null|mixed the fieldset object if a record is round, null if none
 */
function block_credly_get_from_cache($userid=0, $itemtype) {
    global $DB;
    $rec = $DB->get_record('block_credly_cache', array('userid' => $userid, 'itemtype' => $itemtype));
    return $rec;
}

/**
 * Deletes a credly-specific cached value in the database
 *
 * @param int $userid the user id the variable is tied to
 * @param string $itemtype the item type of the variable
 * @return bool true
 */
function block_credly_delete_from_cache($userid=0, $itemtype) {
    global $DB;
    return $DB->delete_records('block_credly_cache', array('userid' => $userid, 'itemtype' => $itemtype));
}

/**
 * Inserts a credly-specific cached value in the database
 *
 * @param int $userid the user id the variable is tied to
 * @param string $itemtype the item type of the variable
 * @param string $value the value of the variable
 * @param int $timeexpires number of seconds to expire a badge after it is awarded
 * @return bool|int true or new id
 */
function block_credly_create_cache_entry($userid=0, $itemtype=null, $value='', $timeexpires=null) {
    global $DB;
    if ($timeexpires === null) {
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

/**
 * Fetch the credly API token
 *
 * @return bool|string false or token string
 */
function block_credly_get_token() {
    $tokenrec = block_credly_get_from_cache(0, 'token');
    if ($tokenrec) {
        return $tokenrec->value;
    } else {
        $resultjson = block_credly_call_api(block_credly_api_endpoint().'authenticate', 'POST');
        if ($resultjson !== false) {
            $result = json_decode($resultjson);
            if (isset($result->meta) && $result->meta->status_code == '200' && $result->meta->status == 'OK') {
                $timeexpires = time() + (60 * 60 * 24 * 7);

                block_credly_create_cache_entry(0, 'token', $result->data->token, $timeexpires);
                block_credly_create_cache_entry(0, 'refresh_token', $result->data->refresh_token, $timeexpires);

                return $result->data->token;
            }
        }
    }

    return false;
}

/**
 * Get the credly member id configured to perform API calls
 *
 * @return bool|int false or the credly member id
 */
function block_credly_get_admin_member_id() {
    global $DB;

    if ($memberinfo = block_credly_get_from_cache(0, 'adminmemberid')) {
        return $memberinfo->value;
    }

    $token = block_credly_get_token();
    if ($token !== false) {
        $email = trim(get_config('block_credly', 'apiuser'));
        $data = array(
            'email' => $email,
            'has_profile' => 0,
            'verbose' => 0,
            'page' => 1,
            'per_page' => 1,
            'order_direction' => 'ASC',
            'access_token' => $token,
        );
        $resultjson = block_credly_call_api(block_credly_api_endpoint().'members', 'GET', $data);
        $result = json_decode($resultjson);
        if (isset($result->meta) && $result->meta->status_code == '200' && $result->meta->status == 'OK') {
            $timeexpires = time() + (60 * 60 * 24 * 90);
            block_credly_create_cache_entry(0, 'adminmemberid', $result->data[0]->id, $timeexpires);
            return $result->data[0]->id;
        }
    }
    return null;
}

/**
 * Get the credly member id of a moodle user.
 * This uses the email to check for a credly member.
 *
 * @param int $userid the moodle user id
 * @return null|int null or the credly member id
 */
function block_credly_get_member_id($userid=null) {
    global $DB;

    if (empty($userid)) {
        global $USER;
        if (empty($USER->id)) {
            return null;
        } else {
            $userid = $USER->id;
        }
    }

    if ($memberinfo = block_credly_get_from_cache($userid, 'memberid')) {
        return $memberinfo->value;
    }

    $token = block_credly_get_token();
    if ($token !== false) {
        $mdluser = $DB->get_record('user', array('id' => $userid));
        if ($mdluser) {
            $email = $mdluser->email;
            $data = array(
                'email' => $email,
                'has_profile' => 0,
                'verbose' => 0,
                'page' => 1,
                'per_page' => 1,
                'order_direction' => 'ASC',
                'access_token' => $token,
            );
            $resultjson = block_credly_call_api(block_credly_api_endpoint().'members', 'GET', $data);
            $result = json_decode($resultjson);
            if (isset($result->meta) && $result->meta->status_code == '200' && $result->meta->status == 'OK') {
                $timeexpires = time() + (60 * 60 * 24 * 30);
                block_credly_create_cache_entry($userid, 'memberid', $result->data[0]->id, $timeexpires);
                return $result->data[0]->id;
            }
        }
    }
    return null;
}

/**
 * Get the credly badges or a user.
 *
 * @param int $userid the moodle user id
 * @return null|mixed null or an object converted from the json returned by credly
 */
function block_credly_get_member_badges($userid=null) {
    global $DB;

    if (empty($userid)) {
        global $USER;
        if (empty($USER->id)) {
            return null;
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
        if ($token !== false) {
            $data = array(
                'page' => 1,
                'per_page' => 1000,
                'order_direction' => 'ASC',
                'access_token' => $token,
            );
            $resultjson = block_credly_call_api(block_credly_api_endpoint().'members/'.$memberid.'/badges', 'GET', $data);
            $result = json_decode($resultjson);
            if (isset($result->meta) && $result->meta->status_code == '200' && $result->meta->status == 'OK') {
                $timeexpires = time() + (60 * 60);
                $badges = $result->data;
                block_credly_create_cache_entry($userid, 'memberbadges', json_encode($badges), $timeexpires);
                return $badges;
            }
        }
    }
    return null;
}

/**
 * Get the credly admin user's created badges.
 * These are the same badges that can be granted/awarded.
 *
 * @return null|mixed null or an object converted from the json returned by credly
 */
function block_credly_get_admin_created_badges() {
    global $DB;

    $memberid = block_credly_get_admin_member_id();

    if ($memberid) {
        if ($memberbadges = block_credly_get_from_cache(0, 'admincreatedbadges')) {
            return json_decode($memberbadges->value);
        }

        $token = block_credly_get_token();
        if ($token !== false) {
            $data = array(
                'page' => 1,
                'per_page' => 1000,
                'order_direction' => 'ASC',
            );
            $resultjson = block_credly_call_api(block_credly_api_endpoint().'members/'.$memberid.'/badges/created', 'GET', $data);
            $result = json_decode($resultjson);
            if (isset($result->meta) && $result->meta->status_code == '200' && $result->meta->status == 'OK') {
                $timeexpires = time() + (60 * 60 * 24);
                $badges = $result->data;
                block_credly_create_cache_entry(0, 'admincreatedbadges', json_encode($badges), $timeexpires);
                return $badges;
            }
        }
    }
    return null;
}

/**
 * Get information for a specific credly badge.
 * These are the same badges that can be granted/awarded.
 *
 * @param int $id the credly badge id
 * @return null|mixed null or an object converted from the json returned by credly
 */
function block_credly_get_badge_info($id) {
    global $DB;

    $token = block_credly_get_token();
    if ($token !== false) {
        $data = array(
            'id' => $id,
            'verbose' => 0,
        );
        $resultjson = block_credly_call_api(block_credly_api_endpoint().'badges/'.$id, 'GET', $data);
        $result = json_decode($resultjson);
        if (isset($result->meta) && $result->meta->status_code == '200' && $result->meta->status == 'OK') {
            $badges = $result->data;
            return $badges;
        }
    }
    return null;
}

/**
 * Create a credly badge
 *
 * @param stdClass $formdata the data to pass to credly
 * @param string $imagedata the file contents of the image for the badge
 * @return int the credly badge id on successful creation
 */
function block_credly_create_badge($formdata, $imagedata) {
    $token = block_credly_get_token();
    if ($token !== false) {
        $data = array(
            'attachment' => base64_encode($imagedata),
            'title' => $formdata->title,
            'short_description' => $formdata->short_description,
            'description' => $formdata->description,
            'criteria' => $formdata->criteria,
            'is_giveable' => $formdata->is_giveable,
            'expires_in' => $formdata->expires_in,
            'access_token' => $token,
        );
        $resultjson = block_credly_call_api(block_credly_api_endpoint().'badges', 'POST', $data);
        $result = json_decode($resultjson);
        if (isset($result->meta) && $result->meta->status_code == '200' && $result->meta->status == 'OK') {
            block_credly_delete_from_cache(0, 'admincreatedbadges');
            $badgeid = $result->data;
            return $badgeid;
        } else {
            print_error('credlyerrorresponse', 'block_credly', '', '('.$result->meta->status.') '.$result->meta->message);
        }
    }
}

/**
 * Update a credly badge
 *
 * @param int $id the credly badge id
 * @param stdClass $formdata the data to pass to credly
 * @param null|string $imagedata the file contents of the image for the badge or null if not updating image
 * @return int the credly badge id on successful update
 */
function block_credly_update_badge($id=null, $formdata, $imagedata=null) {
    if ($id) {
        $token = block_credly_get_token();
        if ($token !== false) {
            $data = array(
                'title' => $formdata->title,
                'short_description' => $formdata->short_description,
                'description' => $formdata->description,
                'criteria' => $formdata->criteria,
                'is_giveable' => $formdata->is_giveable,
                'expires_in' => $formdata->expires_in,
                'access_token' => $token,
            );
            if (!empty($imagedata)) {
                $data['attachment'] = base64_encode($imagedata);
            }
            $resultjson = block_credly_call_api(block_credly_api_endpoint().'badges/'.$id, 'POST', $data);
            $result = json_decode($resultjson);
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

/**
 * Grant a credly badge to a user
 *
 * @param int $userid the moodle user id to grant the badge to
 * @param int $badgeid the credly badge id
 * @return null|mixed null or an object converted from the json returned by credly
 */
function block_credly_grant_badge($userid, $badgeid) {
    global $DB;

    if (!empty($userid)) {
        $token = block_credly_get_token();
        if ($token !== false) {
            $mdluser = $DB->get_record('user', array('id' => $userid));
            if ($mdluser) {
                $data = array(
                    'email' => $mdluser->email,
                    'first_name' => $mdluser->firstname,
                    'last_name' => $mdluser->lastname,
                    'badge_id' => $badgeid,
                    'notify' => 1,
                    'access_token' => $token,
                );
                $resultjson = block_credly_call_api(block_credly_api_endpoint().'member_badges', 'POST', $data);
                $result = json_decode($resultjson);
                if (isset($result->meta) && $result->meta->status_code == '200' && $result->meta->status == 'OK') {
                    block_credly_delete_from_cache($userid, 'memberbadges');
                    return $result->data;
                }
            }
        }
    }
    return null;
}

