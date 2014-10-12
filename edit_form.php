<?php

/**
 * Form for editing credly block instances
 *
 * @package    block_credly
 * @copyright  2014 Deds Castillo, MM Development Services (http://mmmoodle.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class block_credly_edit_form extends block_edit_form {

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
