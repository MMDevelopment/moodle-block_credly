<?php

/**
 * Script that grants a credly badge to a user
 *
 * @package    block_credly
 * @copyright  2014 Deds Castillo, MM Development Services (http://mmmoodle.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(__FILE__) . '/../../config.php');
require_once(dirname(__FILE__).'/locallib.php');
require_once(dirname(__FILE__).'/lib.php');

$returnurl = optional_param('returnurl', '', PARAM_LOCALURL);
$courseid = optional_param('courseid', 0, PARAM_INT);
$id = optional_param('id', 0, PARAM_INT);
$grant = optional_param('grant', false, PARAM_BOOL);
$grantselect = optional_param('grantselect', 0, PARAM_INT);

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

require_capability('block/credly:grantbadge', $context);

$urlparams = array('id' => $id);
if ($courseid) {
    $urlparams['courseid'] = $courseid;
}
if ($returnurl) {
    $urlparams['returnurl'] = $returnurl;
}

$PAGE->set_url('/blocks/credly/grantbadge.php', $urlparams);
$PAGE->set_pagelayout('standard');

$strviewbadge = get_string('grantbadge', 'block_credly');

$PAGE->set_title($strviewbadge);
$PAGE->set_heading($strviewbadge);

$PAGE->navbar->add(get_string('blocks'));
$PAGE->navbar->add(get_string('pluginname', 'block_credly'));
$PAGE->navbar->add($strviewbadge);

$errormsg = '';
if ($grant && confirm_sesskey()) {
    $errormsgs = array();
    if (isset($_POST['cancel'])) {
        if ($courseid) {
            redirect(new moodle_url('/course/view.php?id='.$courseid));
        } else {
            redirect(new moodle_url('/'));
        }
    }
    if (isset($_POST['submitbutton'])) {
        if (!$grantselect) {
            $errormsgs[] = get_string('grantuserrequired', 'block_credly');
        }
        if (!$id) {
            $errormsgs[] = get_string('grantbadgerequired', 'block_credly');
        }
        if (count($errormsgs) == 0) {
            $grant_result = block_credly_grant_badge($grantselect, $id);
            if (empty($grant_result)) {
                $errormsgs[] = get_string('granterror', 'block_credly');
            } else {
                if ($courseid) {
                    $redirecturl = new moodle_url('/course/view.php?id='.$courseid);
                } else {
                    $redirecturl = new moodle_url('/');
                }
                redirect($redirecturl, get_string('grantedtouser', 'block_credly'), 5);
            }
        }
        if (count($errormsgs) > 0) {
            $errormsg = implode('<br />', $errormsgs);
        }
    }
}

echo $OUTPUT->header();

if (!empty($errormsg)) {
    echo html_writer::div($errormsg, 'block_credly_errorbox');
}

echo html_writer::start_tag('form', array('action' => 'grantbadge.php', 'method' => 'post'));
echo html_writer::empty_tag('input', array('type'=>'hidden', 'name'=>'courseid', 'value'=>$courseid));
echo html_writer::empty_tag('input', array('type'=>'hidden', 'name'=>'sesskey', 'value'=>sesskey()));
echo html_writer::empty_tag('input', array('type'=>'hidden', 'name'=>'grant', 'value'=>1));

$options = array('courseid' => $courseid, 'accesscontext' => $context, 'searchanywhere' => TRUE, 'multiselect' => FALSE);
$potentialuserselector = new block_credly_potential_grantee('grantselect', $options);
$potentialuserselector->set_rows(10);
$potentialuserselector->display();

$badgeselectoptions = array();
$badges = block_credly_get_admin_created_badges();
foreach ($badges as $badge) {
    $badgeselectoptions[$badge->id] = $badge->title;
}
asort($badgeselectoptions, SORT_STRING);
echo html_writer::start_tag('div');
echo html_writer::label(get_string('selectbadgeaward', 'block_credly'), 'id');
echo html_writer::select($badgeselectoptions, 'id', $id);
echo html_writer::end_tag('div');

echo html_writer::start_tag('div');
echo html_writer::empty_tag('input', array('type'=>'submit', 'name'=>'submitbutton', 'value'=>get_string('issuebadge','block_credly'), 'id'=>'id_submitbutton'));
echo html_writer::empty_tag('input', array('type'=>'submit', 'name'=>'cancel', 'value'=>get_string('cancel'), 'id'=>'id_cancel', 'class'=>'btn-cancel'));
echo html_writer::end_tag('div');

echo html_writer::end_tag('form');

echo $OUTPUT->footer();
