<?php

/**
 * Script that displays a single Credly badge information
 *
 * @package    block_credly
 * @copyright  2014 Deds Castillo, MM Development Services (http://mmmoodle.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(__FILE__) . '/../../config.php');
require_once(dirname(__FILE__).'/lib.php');

require_login();
if (isguestuser()) {
    print_error('guestsarenotallowed');
}

$returnurl = optional_param('returnurl', '', PARAM_LOCALURL);
$courseid = optional_param('courseid', 0, PARAM_INT);
$id = required_param('id', PARAM_INT);

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

$urlparams = array('id' => $id);
if ($courseid) {
    $urlparams['courseid'] = $courseid;
}
if ($returnurl) {
    $urlparams['returnurl'] = $returnurl;
}

$PAGE->set_url('/blocks/credly/viewbadge.php', $urlparams);
$PAGE->set_pagelayout('standard');

$strviewbadge = get_string('viewbadge', 'block_credly');

$PAGE->set_title($strviewbadge);
$PAGE->set_heading($strviewbadge);

$PAGE->navbar->add(get_string('blocks'));
$PAGE->navbar->add(get_string('pluginname', 'block_credly'));
if (has_capability('block/credly:managebadge', $context)) {
    $managebadges = new moodle_url('/blocks/credly/managebadges.php', $urlparams);
    $PAGE->navbar->add(get_string('managebadges', 'block_credly'), $managebadges);
}
$PAGE->navbar->add($strviewbadge);
echo $OUTPUT->header();

$badgeinfo = block_credly_get_badge_info($id);
if ($course) {
    if (has_capability('block/credly:managebadge', $context)) {
        $continueurl = new moodle_url('/blocks/credly/managebadges.php', array('courseid'=>$courseid));
    } else {
        $continueurl = new moodle_url('/course/view.php', array('id'=>$courseid));
    }
} else {
    $continueurl = new moodle_url('/blocks/credly/managebadges.php', array('courseid'=>$courseid));
}
if (empty($badgeinfo)) {
    echo $OUTPUT->box_start('generalbox boxaligncenter');
    echo html_writer::div(get_string('errorfetchingbadgeinfo', 'block_credly'), 'block_credly_errorbox');
    echo html_writer::div($OUTPUT->single_button($continueurl, get_string('continue'), 'get'));
    echo $OUTPUT->box_end();
} else {
    $table = new html_table();
    $rows_data = array();

    $badge_image_src = str_replace('.png', '_13.png', $badgeinfo->image_url);
    $imagepreview = html_writer::img($badge_image_src, $badgeinfo->title, array('title'=>$badgeinfo->title));

    $imagelink = html_writer::link($badgeinfo->image_url, $badgeinfo->image_url, array('target'=>'_blank'));

    $rows_data[] = array(get_string('id','block_credly'), $badgeinfo->id);
    $rows_data[] = array(get_string('title','block_credly'), $badgeinfo->title);
    $rows_data[] = array(get_string('short_description','block_credly'), $badgeinfo->short_description);
    $rows_data[] = array(get_string('image_url','block_credly'), $imagelink);
    $rows_data[] = array(get_string('image_preview','block_credly'), $imagepreview);
    $rows_data[] = array(get_string('description','block_credly'), $badgeinfo->description);
    $rows_data[] = array(get_string('criteria','block_credly'), $badgeinfo->criteria);
    if (has_capability('block/credly:managebadge', $context)) {
        $isgiveable = ($badgeinfo->is_giveable == 1) ? get_string('yes') : get_string('no');
        $rows_data[] = array(get_string('is_giveable','block_credly'), $isgiveable);
        $rows_data[] = array(get_string('created_at','block_credly'), $badgeinfo->created_at);
        // $rows_data[] = array(get_string('is_claimable','block_credly'), $badgeinfo->is_claimable);
        $expiresin = ($badgeinfo->expires_in != 0) ? $badgeinfo->expires_in : get_string('never');
        $rows_data[] = array(get_string('expires_in','block_credly'), $expiresin);
    }
    $table->data = $rows_data;

    echo html_writer::div($OUTPUT->single_button($continueurl, get_string('back'), 'get'));
    echo html_writer::table($table);

}

echo $OUTPUT->footer();
