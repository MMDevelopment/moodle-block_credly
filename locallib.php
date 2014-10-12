<?php

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

class block_credly_badge_edit_form extends moodleform {
    protected $_isadding;
    protected $_image_maxbytes = 1048576;
    // credly limits to 51 years
    protected $_max_expires_in = 1609462800;

    function __construct($actionurl=null, $customdata=null, $isadding=false) {
        $this->_isadding = $isadding;
        parent::moodleform($actionurl, $customdata);
    }

    function definition() {
        $mform =& $this->_form;

        if (!$this->_isadding) {
            $mform->addElement('static', 'imagepreview', get_string('image_preview_current', 'block_credly'), $this->_customdata['imagepreview']);
        }
        $mform->addElement('filepicker', 'badgeimage', get_string('badgeimage', 'block_credly'), null, array('maxbytes'=>$this->_image_maxbytes, 'accepted_types'=>array('image')));
        if ($this->_isadding) {
            $mform->addRule('badgeimage', get_string('missingbadgeimage', 'block_credly'), 'required', null, 'client');
        }
        $mform->addHelpButton('badgeimage', 'badgeimage', 'block_credly');

        $mform->addElement('text', 'title', get_string('title', 'block_credly'), 'maxlength="128" size="80"');
        $mform->addRule('title', get_string('missingtitle', 'block_credly'), 'required', null, 'client');
        $mform->addHelpButton('title', 'title', 'block_credly');

        $mform->addElement('text', 'short_description', get_string('short_description', 'block_credly'), 'maxlength="128" size="80"');
        $mform->addHelpButton('short_description', 'short_description', 'block_credly');

        $mform->addElement('text', 'description', get_string('description', 'block_credly'), 'maxlength="500" size="80"');
        $mform->addHelpButton('description', 'description', 'block_credly');

        $mform->addElement('text', 'criteria', get_string('criteria', 'block_credly'), 'maxlength="500" size="80"');
        $mform->addHelpButton('criteria', 'criteria', 'block_credly');

        $mform->addElement('selectyesno', 'is_giveable', get_string('is_giveable', 'block_credly'));
        $mform->addHelpButton('is_giveable', 'is_giveable', 'block_credly');
        $mform->setDefault('is_giveable', 0);

        $mform->addElement('text', 'expires_in', get_string('expires_in', 'block_credly'), 'maxlength="10" size="11"');
        $mform->addRule('expires_in', get_string('errorexpiresin', 'block_credly', $this->_max_expires_in), 'numeric', null, 'client');
        $mform->setDefault('expires_in', 0);
        $mform->addHelpButton('expires_in', 'expires_in', 'block_credly');


        $submitlabal = null; // Default
        if ($this->_isadding) {
            $submitlabal = get_string('addnewbadge', 'block_credly');
        }
        $this->add_action_buttons(true, $submitlabal);
    }

    function validation($data, $files) {
        $errors = parent::validation($data, $files);

        if (intval($data['expires_in']) > $this->_max_expires_in) {
            $errors['expires_in'] = get_string('errorexpiresin', 'block_credly', $this->_max_expires_in);
        }

        return $errors;
    }

}

class block_credly_potential_grantee extends user_selector_base {
    protected $_courseid;

    public function __construct($name, $options) {
        $this->_courseid  = $options['courseid'];
        parent::__construct($name, $options);
    }

    /**
     * Candidate users
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

    protected function get_options() {
        $options = parent::get_options();
        $options['courseid'] = $this->_courseid;
        $options['file']    = 'blocks/credly/locallib.php';
        return $options;
    }
}
