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
 * List of a user's badges
 *
 * @package    block_credly
 * @copyright  2014-2017 Deds Castillo, MM Development Services (http://michaelmino.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(__FILE__) . '/../../config.php');
require_once($CFG->libdir . '/tablelib.php');
require_once(dirname(__FILE__).'/lib.php');

require_login();

$returnurl = optional_param('returnurl', '', PARAM_LOCALURL);
$courseid = optional_param('courseid', 0, PARAM_INT);

if ($courseid == SITEID) {
    $courseid = 0;
}
if ($courseid) {
    $course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);
    $PAGE->set_course($course);
    $PAGE->set_context(context_course::instance($courseid));
} else {
    $context = context_system::instance();
    $PAGE->set_context($context);
}

$urlparams = array();
$extraparams = '';
if ($courseid) {
    $urlparams['courseid'] = $courseid;
    $extraparams = '&courseid=' . $courseid;
}
if ($returnurl) {
    $urlparams['returnurl'] = $returnurl;
    $extraparams = '&returnurl=' . $returnurl;
}

$baseurl = new moodle_url('/blocks/credly/mybadges.php', $urlparams);
$PAGE->set_url($baseurl);

$badges = block_credly_get_member_badges();

$strmy = get_string('mybadges', 'block_credly');

$PAGE->set_pagelayout('standard');
$PAGE->set_title($strmy);
$PAGE->set_heading($strmy);

$PAGE->navbar->add(get_string('blocks'));
$PAGE->navbar->add(get_string('pluginname', 'block_credly'));
$PAGE->navbar->add($strmy, $baseurl);
echo $OUTPUT->header();
echo $OUTPUT->heading($strmy, 2);

$table = new flexible_table('credly-display-badges');

$table->define_columns(array('thumbnail', 'info'));
$table->define_headers(array('', ''));
$table->define_baseurl($baseurl);

$table->set_attribute('cellspacing', '0');
$table->set_attribute('id', 'credlybadges');
$table->set_attribute('class', 'generaltable generalbox');
$table->column_class('thumbnail', 'badgethumbnail');
$table->column_class('info', 'info');

$table->setup();

if ($badges) {
    foreach ($badges as $badge) {

        $badgeimagesrc = str_replace('.png', '_5.png', $badge->image);
        $badgeimagestub = html_writer::img($badgeimagesrc, $badge->title, array('title' => $badge->title));

        $badgelink = html_writer::link(
            'https://credly.com/credit/'.$badge->id,
            get_string('viewincredly', 'block_credly'),
            array('target' => '_blank')
        );
        $badgeinfo = html_writer::tag('span', $badge->title, array('class' => 'credly_badge_title'));
        $badgeinfo .= html_writer::empty_tag('br');
        $badgeinfo .= $badge->description;
        $badgeinfo .= html_writer::empty_tag('br');
        $badgeinfo .= $badgelink;

        $table->add_data(array($badgeimagestub, $badgeinfo));
    }

}

$table->print_html();

echo $OUTPUT->footer();
