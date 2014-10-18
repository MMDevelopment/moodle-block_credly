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
        $continueurl = new moodle_url('/blocks/credly/managebadges.php', array('courseid' => $courseid));
    } else {
        $continueurl = new moodle_url('/course/view.php', array('id' => $courseid));
    }
} else {
    $continueurl = new moodle_url('/blocks/credly/managebadges.php', array('courseid' => $courseid));
}
if (empty($badgeinfo)) {
    echo $OUTPUT->box_start('generalbox boxaligncenter');
    echo html_writer::div(get_string('errorfetchingbadgeinfo', 'block_credly'), 'block_credly_errorbox');
    echo html_writer::div($OUTPUT->single_button($continueurl, get_string('continue'), 'get'));
    echo $OUTPUT->box_end();
} else {
    $table = new html_table();
    $rowsdata = array();

    $badgeimagesrc = str_replace('.png', '_13.png', $badgeinfo->image_url);
    $imagepreview = html_writer::img($badgeimagesrc, $badgeinfo->title, array('title' => $badgeinfo->title));

    $imagelink = html_writer::link($badgeinfo->image_url, $badgeinfo->image_url, array('target' => '_blank'));

    $rowsdata[] = array(get_string('id', 'block_credly'), $badgeinfo->id);
    $rowsdata[] = array(get_string('title', 'block_credly'), $badgeinfo->title);
    $rowsdata[] = array(get_string('short_description', 'block_credly'), $badgeinfo->short_description);
    $rowsdata[] = array(get_string('image_url', 'block_credly'), $imagelink);
    $rowsdata[] = array(get_string('image_preview', 'block_credly'), $imagepreview);
    $rowsdata[] = array(get_string('description', 'block_credly'), $badgeinfo->description);
    $rowsdata[] = array(get_string('criteria', 'block_credly'), $badgeinfo->criteria);
    if (has_capability('block/credly:managebadge', $context)) {
        $isgiveable = ($badgeinfo->is_giveable == 1) ? get_string('yes') : get_string('no');
        $rowsdata[] = array(get_string('is_giveable', 'block_credly'), $isgiveable);
        $rowsdata[] = array(get_string('created_at', 'block_credly'), $badgeinfo->created_at);
        // $rowsdata[] = array(get_string('is_claimable', 'block_credly'), $badgeinfo->is_claimable);
        $expiresin = ($badgeinfo->expires_in != 0) ? $badgeinfo->expires_in : get_string('never');
        $rowsdata[] = array(get_string('expires_in', 'block_credly'), $expiresin);
    }
    $table->data = $rowsdata;

    echo html_writer::div($OUTPUT->single_button($continueurl, get_string('back'), 'get'));
    echo html_writer::table($table);

}

echo $OUTPUT->footer();
