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
 * Settings for the credly block
 *
 * @package    block_credly
 * @copyright  2014 Deds Castillo, MM Development Services (http://mmmoodle.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

if ($ADMIN->fulltree) {
    // ... general settings ...
    $settings->add(new admin_setting_configtext('block_credly/apikey', get_string('apikey', 'block_credly'),
            get_string('apikeydesc', 'block_credly'), '', PARAM_RAW_TRIMMED));
    $settings->add(new admin_setting_configtextarea('block_credly/apisecret', get_string('apisecret', 'block_credly'),
            get_string('apisecretdesc', 'block_credly'), '', PARAM_RAW_TRIMMED));
    $settings->add(new admin_setting_configtext('block_credly/apiuser', get_string('apiuser', 'block_credly'),
            get_string('apiuserdesc', 'block_credly'), '', PARAM_RAW_TRIMMED));
    $settings->add(new admin_setting_configtext('block_credly/apipassword', get_string('apipassword', 'block_credly'),
            get_string('apipassworddesc', 'block_credly'), '', PARAM_RAW_TRIMMED));
}
