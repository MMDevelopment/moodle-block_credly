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
 * Script that creates/upload a badge via Credly Open Credit API
 *
 * @package    block_credly
 * @copyright  2014-2017 Deds Castillo, MM Development Services (http://michaelmino.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(__FILE__) . '/../../config.php');
require_once(dirname(__FILE__) . '/lib.php');
require_once(dirname(__FILE__) . '/locallib.php');

$returnurl = optional_param('returnurl', '', PARAM_LOCALURL);
$courseid = optional_param('courseid', 0, PARAM_INT);
$id = optional_param('id', 0, PARAM_INT); // 0 mean create new.

if ($courseid == SITEID) {
    $courseid = 0;
}
if ($courseid) {
    $course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);
    $PAGE->set_course($course);
    $context = $PAGE->context;
} else {
    $context = context_system::instance();
    $PAGE->set_context($context);
}

require_capability('block/credly:managebadge', $context);

$urlparams = array('id' => $id);
if ($courseid) {
    $urlparams['courseid'] = $courseid;
}
if ($returnurl) {
    $urlparams['returnurl'] = $returnurl;
}
$managebadges = new moodle_url('/blocks/credly/managebadges.php', $urlparams);

$PAGE->set_url('/blocks/credly/editbadge.php', $urlparams);
$PAGE->set_pagelayout('admin');

if ($id) {
    $isadding = false;
    $badgerecord = block_credly_get_badge_info($id);
    if (!$badgerecord) {
        print_error('fetcherror', 'block_credly');
    }
    $badgeimagepreviewsrc = str_replace('.png', '_7.png', $badgerecord->image_url);
    $imagepreview = html_writer::img($badgeimagepreviewsrc, $badgerecord->title, array('title' => $badgerecord->title));
    $customdata = array('imagepreview' => $imagepreview);
} else {
    $isadding = true;
    $badgerecord = new stdClass;
    $customdata = null;
}

$mform = new block_credly_badge_edit_form($PAGE->url, $customdata, $isadding);
$mform->set_data($badgerecord);

if ($mform->is_cancelled()) {
    redirect($managebadges);

} else if ($data = $mform->get_data() and confirm_sesskey()) {
    if ($isadding) {
        $imagedata = $mform->get_file_content('badgeimage');
        $badgeid = block_credly_create_badge($data, $imagedata);
    } else {
        $imagedata = $mform->get_file_content('badgeimage');
        $badgeid = block_credly_update_badge($id, $data, $imagedata);
    }

    redirect($managebadges);

} else {
    if ($isadding) {
        $strtitle = get_string('createnewbadge', 'block_credly');
    } else {
        $strtitle = get_string('editbadge', 'block_credly');
    }

    $PAGE->set_title($strtitle);
    $PAGE->set_heading($strtitle);

    $PAGE->navbar->add(get_string('blocks'));
    $PAGE->navbar->add(get_string('pluginname', 'block_credly'));
    $PAGE->navbar->add(get_string('managebadges', 'block_credly'), $managebadges );
    $PAGE->navbar->add($strtitle);

    echo $OUTPUT->header();
    echo $OUTPUT->heading($strtitle, 2);

    $mform->display();

    echo $OUTPUT->footer();
}

