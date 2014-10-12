<?php

/**
 * Script that lists created Credly badges
 *
 * @package    block_credly
 * @copyright  2014 Deds Castillo, MM Development Services (http://mmmoodle.com)
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
    $context = $PAGE->context;
} else {
    $context = context_system::instance();
    $PAGE->set_context($context);
}

require_capability('block/credly:managebadge', $context);

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

$baseurl = new moodle_url('/blocks/credly/managebadges.php', $urlparams);
$PAGE->set_url($baseurl);

$badges = block_credly_get_admin_created_badges();

$strmanage = get_string('managebadges', 'block_credly');

$PAGE->set_pagelayout('standard');
$PAGE->set_title($strmanage);
$PAGE->set_heading($strmanage);

$managebadges = new moodle_url('/blocks/credly/managebadges.php', $urlparams);
$PAGE->navbar->add(get_string('blocks'));
$PAGE->navbar->add(get_string('pluginname', 'block_credly'));
$PAGE->navbar->add(get_string('managebadges', 'block_credly'), $managebadges);
echo $OUTPUT->header();

$table = new flexible_table('credly-display-badges');

$table->define_columns(array('id', 'thumbnail', 'title', 'short_description', 'actions'));
$table->define_headers(array(
    get_string('id', 'block_credly'),
    get_string('thumbnail', 'block_credly'),
    get_string('title', 'block_credly'),
    get_string('short_description', 'block_credly'),
    get_string('actions', 'moodle')));
$table->define_baseurl($baseurl);

$table->set_attribute('cellspacing', '0');
$table->set_attribute('id', 'credlybadges');
$table->set_attribute('class', 'generaltable generalbox');
$table->column_class('id', 'id');
$table->column_class('thumbnail', 'badgethumbnail');
$table->column_class('title', 'title');
$table->column_class('short_description', 'shortdesc');
$table->column_class('actions', 'actions');

$table->setup();

foreach($badges as $badge) {

    $badge_image_src = str_replace('.png', '_5.png', $badge->image_url);
    $badge_image_stub = html_writer::img($badge_image_src, $badge->title, array('title'=>$badge->title));

    $viewlink = html_writer::link($CFG->wwwroot . '/blocks/credly/viewbadge.php?id=' . $badge->id . $extraparams, $badge->title);

    $editurl = new moodle_url('/blocks/credly/editbadge.php?id=' . $badge->id . $extraparams);
    $editaction = $OUTPUT->action_icon($editurl, new pix_icon('t/edit', get_string('edit')));
    $actions_stub = $editaction;

    $table->add_data(array($badge->id, $badge_image_stub, $viewlink, $badge->short_description, $actions_stub));
}

$url = $CFG->wwwroot . '/blocks/credly/editbadge.php?' . substr($extraparams, 1);
echo html_writer::div($OUTPUT->single_button($url, get_string('addnewbadge', 'block_credly'), 'get'), 'actionbuttons');

$table->print_html();

echo html_writer::div($OUTPUT->single_button($url, get_string('addnewbadge', 'block_credly'), 'get'), 'actionbuttons');

echo $OUTPUT->footer();