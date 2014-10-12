<?php

/**
 * A block that provides integration with Credly Open Credit API
 *
 * @package    block_credly
 * @copyright  2014 Deds Castillo, MM Development Services (http://mmmoodle.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(__FILE__).'/lib.php');

class block_credly extends block_base {

    public function init() {
        $this->title = get_string('credlybadges', 'block_credly');
    }

    public function instance_allow_multiple() {
        return false;
    }

    public function has_config() {
        return true;
    }

    public function applicable_formats() {
        return array('all' => true);
    }

    public function instance_allow_config() {
        return true;
    }

    public function cron() {
        global $DB;
        $starttime =  microtime();
        mtrace('');
        $DB->delete_records_select('block_credly_cache', 'timeexpires < ?', array(time()));
        mtrace('... used '.microtime_diff($starttime, microtime()) . ' seconds)');
        return true;
    }
    public function get_content() {
        global $CFG;

        if ($this->content !== null) {
            return $this->content;
        }

        $this->content         =  new stdClass;
        $this->content->text   = '';

        $badges = block_credly_get_member_badges();

        $maxentries = 5;
        if ( !empty($this->config->shownum) ) {
            $maxentries = intval($this->config->shownum);
        }
        if ($maxentries > 10) {
            $maxentries = 10;
        }
        if ($maxentries > count($badges)) {
            $maxentries = count($badges);
        }
        for ($x = 0; $x < $maxentries; $x++) {
            $badge = $badges[$x];
            // use the thumbnail
            $badge_image_src = str_replace('.png', '_5.png', $badge->image);
            $badge_image_stub = html_writer::img($badge_image_src, $badge->title, array('title'=>$badge->title, 'class'=>'credly_badge credly_badge_id_'.$badge->id));
            $badge_link = html_writer::link('https://credly.com/credit/'.$badge->id, $badge_image_stub, array('target'=>'_blank'));
            $this->content->text .= $badge_link;
        }

        $footer_items = array();
        if ($this->page->course->id !== SITEID && has_capability('block/credly:grantbadge', $this->context)) {
            $footer_items[] = html_writer::link($CFG->wwwroot . '/blocks/credly/grantbadge.php?courseid='.$this->page->course->id, get_string('grantbadge', 'block_credly'));
        }
        if (has_capability('block/credly:managebadge', $this->context)) {
            $footer_items[] = html_writer::link($CFG->wwwroot . '/blocks/credly/managebadges.php?courseid='.$this->page->course->id, get_string('managebadges', 'block_credly'));
        }
        if (count($footer_items)>0) {
            $this->content->footer = html_writer::alist($footer_items);
        }

        return $this->content;
    }
}