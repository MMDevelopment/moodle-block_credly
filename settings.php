<?php

/**
 * Settings for the credly block
 *
 * @package    block_credly
 * @copyright  2014 Deds Castillo, MM Development Services (http://mmmoodle.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

if ($ADMIN->fulltree) {
    //--- general settings -----------------------------------------------------------------------------------
    $settings->add(new admin_setting_configtext('block_credly/apikey', get_string('apikey', 'block_credly'),
                       get_string('apikeydesc', 'block_credly'), '', PARAM_RAW_TRIMMED));
    $settings->add(new admin_setting_configtextarea('block_credly/apisecret', get_string('apisecret', 'block_credly'),
                       get_string('apisecretdesc', 'block_credly'), '', PARAM_RAW_TRIMMED));
    $settings->add(new admin_setting_configtext('block_credly/apiuser', get_string('apiuser', 'block_credly'),
                       get_string('apiuserdesc', 'block_credly'), '', PARAM_RAW_TRIMMED));
    $settings->add(new admin_setting_configtext('block_credly/apipassword', get_string('apipassword', 'block_credly'),
                       get_string('apipassworddesc', 'block_credly'), '', PARAM_RAW_TRIMMED));
}
