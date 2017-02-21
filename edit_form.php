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
 * Form for editing credly block instances
 *
 * @package    block_credly
 * @copyright  2014-2017 Deds Castillo, MM Development Services (http://michaelmino.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Form to customize block parameters.
 *
 * @package    block_credly
 * @copyright  2014-2017 Deds Castillo, MM Development Services (http://michaelmino.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_credly_edit_form extends block_edit_form {

    /**
     * This generates the form for setting parameters of a credly block instance
     *
     * @param moodleform $mform The edit form
     */
    protected function specific_definition($mform) {
        global $CFG, $DB, $USER;

        // Fields for editing block contents.
        $mform->addElement('header', 'configheader', get_string('blocksettings', 'block'));

        $mform->addElement('text', 'config_shownum', get_string('shownumlabel', 'block_credly'), array('size' => 5));
        $mform->setType('config_shownum', PARAM_INT);
        $mform->addRule('config_shownum', null, 'numeric', null, 'client');
        $mform->setDefault('config_shownum', 5);

    }
}
