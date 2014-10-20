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
 * Helper forms for integrating with Credly Open Credit API
 *
 * @package    block_credly
 * @copyright  2014 Deds Castillo, MM Development Services (http://mmmoodle.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

global $CFG;
require_once($CFG->libdir . '/formslib.php');
require_once($CFG->dirroot . '/user/selector/lib.php');

/**
 * Form for adding or editing badges.
 *
 * @package    block_credly
 * @copyright  2014 Deds Castillo, MM Development Services (http://mmmoodle.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_credly_badge_edit_form extends moodleform {
    /** @var bool This variable tracks whether adding or editing a badge */
    protected $_isadding;

    /** @var int This variable specifies the maximum image file size (in bytes) for badge */
    protected $_imagemaxbytes = 1048576;

    /**
     * This specifies the maximum time in seconds from date of granting
     * of a badge before it expires.  The Credly maximum is 51 years.
     * @var int
     */
    protected $_maxexpiresin = 1609462800;

    /**
     * Constructor for the badge edit form
     *
     * @param null|string $actionurl action to call for the form, null uses default
     * @param null|mixed $customdata the custom data for the moodleform class
     * @param bool $isadding true if creating new badge, false if updating an existing badge
     */
    public function __construct($actionurl=null, $customdata=null, $isadding=false) {
        $this->_isadding = $isadding;
        parent::__construct($actionurl, $customdata);
    }

    /**
     * The edit form definition
     *
     */
    protected function definition() {
        $mform =& $this->_form;

        if (!$this->_isadding) {
            $mform->addElement('static', 'imagepreview', get_string('image_preview_current', 'block_credly'),
                    $this->_customdata['imagepreview']);
        }
        $mform->addElement('filepicker', 'badgeimage', get_string('badgeimage', 'block_credly'),
                    null, array('maxbytes' => $this->_imagemaxbytes, 'accepted_types' => array('image')));
        if ($this->_isadding) {
            $mform->addRule('badgeimage', get_string('missingbadgeimage', 'block_credly'), 'required', null, 'client');
        }
        $mform->addHelpButton('badgeimage', 'badgeimage', 'block_credly');

        $mform->addElement('text', 'title', get_string('title', 'block_credly'), 'maxlength="128" size="80"');
        $mform->setType('title', PARAM_RAW);
        $mform->addRule('title', get_string('missingtitle', 'block_credly'), 'required', null, 'client');
        $mform->addHelpButton('title', 'title', 'block_credly');

        $mform->addElement('text', 'short_description', get_string('short_description', 'block_credly'),
                'maxlength="128" size="80"');
        $mform->setType('short_description', PARAM_RAW);
        $mform->addHelpButton('short_description', 'short_description', 'block_credly');

        $mform->addElement('text', 'description', get_string('description', 'block_credly'), 'maxlength="500" size="80"');
        $mform->setType('description', PARAM_RAW);
        $mform->addHelpButton('description', 'description', 'block_credly');

        $mform->addElement('text', 'criteria', get_string('criteria', 'block_credly'), 'maxlength="500" size="80"');
        $mform->setType('criteria', PARAM_RAW);
        $mform->addHelpButton('criteria', 'criteria', 'block_credly');

        $mform->addElement('selectyesno', 'is_giveable', get_string('is_giveable', 'block_credly'));
        $mform->addHelpButton('is_giveable', 'is_giveable', 'block_credly');
        $mform->setDefault('is_giveable', 0);

        $mform->addElement('text', 'expires_in', get_string('expires_in', 'block_credly'), 'maxlength="10" size="11"');
        $mform->setType('expires_in', PARAM_INT);
        $mform->addRule('expires_in', get_string('errorexpiresin', 'block_credly', $this->_maxexpiresin),
                'numeric', null, 'client');
        $mform->setDefault('expires_in', 0);
        $mform->addHelpButton('expires_in', 'expires_in', 'block_credly');

        $submitlabal = null; // The default label.
        if ($this->_isadding) {
            $submitlabal = get_string('addnewbadge', 'block_credly');
        }
        $this->add_action_buttons(true, $submitlabal);
    }

    /**
     * The edit form validation code
     *
     * @param array $data form data
     * @param array $files
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        if (intval($data['expires_in']) > $this->_maxexpiresin) {
            $errors['expires_in'] = get_string('errorexpiresin', 'block_credly', $this->_maxexpiresin);
        }

        return $errors;
    }

}

/**
 * A user selector widget for fetching potential grantees of a badge
 *
 * @package    block_credly
 * @copyright  2014 Deds Castillo, MM Development Services (http://mmmoodle.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_credly_potential_grantee extends user_selector_base {
    /** @var int The course id where to search potential grantees */
    protected $_courseid;

    /**
     * Constructor for the user selector form
     *
     * @param null|string $name name of the widget
     * @param array $options options passed to the widget
     */
    public function __construct($name, $options) {
        $this->_courseid  = $options['courseid'];
        parent::__construct($name, $options);
    }

    /**
     * Candidate users
     *
     * @param string $search
     * @return array
     */
    public function find_users($search) {
        global $DB;
        // By default wherecondition retrieves all users except the deleted, not confirmed and guest.
        list($wherecondition, $params) = $this->search_sql($search, 'u');

        $fields      = 'SELECT ' . $this->required_fields_sql('u');
        $countfields = 'SELECT COUNT(1)';

        $sql = " FROM {user} u
            JOIN {user_enrolments} ue ON (ue.userid = u.id)
            JOIN {enrol} e ON (ue.enrolid = e.id)
                WHERE $wherecondition";

        if ($this->_courseid != 0) {
            $sql .= " AND e.courseid = :courseid";
            $params['courseid'] = $this->_courseid;
        }

        list($sort, $sortparams) = users_order_by_sql('u', $search, $this->accesscontext);
        $order = ' ORDER BY ' . $sort;

        if (!$this->is_validating()) {
            $potentialmemberscount = $DB->count_records_sql($countfields . $sql, $params);
            if ($potentialmemberscount > $this->maxusersperpage) {
                return $this->too_many_results($search, $potentialmemberscount);
            }
        }

        $availableusers = $DB->get_records_sql($fields . $sql . $order, array_merge($params, $sortparams));

        if (empty($availableusers)) {
            return array();
        }

        if ($search) {
            $groupname = get_string('granttocandidatesmatching', 'block_credly', $search);
        } else {
            $groupname = get_string('granttocandidates', 'block_credly');
        }

        return array($groupname => $availableusers);
    }

    /**
     * Get options for the widget
     *
     * @return array
     */
    protected function get_options() {
        $options = parent::get_options();
        $options['courseid'] = $this->_courseid;
        $options['file']    = 'blocks/credly/locallib.php';
        return $options;
    }
}
