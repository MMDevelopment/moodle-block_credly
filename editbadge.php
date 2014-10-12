<?php

/**
 * Script that creates/upload a badge via Credly Open Credit API
 *
 * @package    block_credly
 * @copyright  2014 Deds Castillo, MM Development Services (http://mmmoodle.com)
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
    $badge_image_preview_src = str_replace('.png', '_7.png', $badgerecord->image_url);
    $imagepreview = html_writer::img($badge_image_preview_src, $badgerecord->title, array('title'=>$badgerecord->title));
    $customdata = array('imagepreview'=>$imagepreview);
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
        $image_data = $mform->get_file_content('badgeimage');
        $badgeid = block_credly_create_badge($data, $image_data);
    } else {
        $image_data = $mform->get_file_content('badgeimage');
        $badgeid = block_credly_update_badge($id, $data, $image_data);
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

