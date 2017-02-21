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
 * A block that provides integration with Credly Open Credit API
 *
 * @package    block_credly
 * @copyright  2014-2017 Deds Castillo, MM Development Services (http://michaelmino.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once(dirname(__FILE__).'/lib.php');

/**
 * The main class for the block.
 *
 * @package    block_credly
 * @copyright  2014-2017 Deds Castillo, MM Development Services (http://michaelmino.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_credly extends block_base {

    /**
     * Block initialization.
     *
     */
    public function init() {
        $this->title = get_string('credlybadges', 'block_credly');
    }

    /**
     * Allow the block to have multiple instances per context.
     *
     * @return bool
     */
    public function instance_allow_multiple() {
        return false;
    }

    /**
     * Allow the block to have a configuration page
     *
     * @return boolean
     */
    public function has_config() {
        return true;
    }

    /**
     * Locations where block can be displayed
     *
     * @return array the locations and whether to allow display
     */
    public function applicable_formats() {
        return array('all' => true);
    }

    /**
     * Allow to configure per instance
     *
     * @return boolean
     */
    public function instance_allow_config() {
        return true;
    }

    /**
     * The script to run for this block on cron execution
     *
     * @return boolean
     */
    public function cron() {
        global $DB;
        $starttime = microtime();
        mtrace('');
        $DB->delete_records_select('block_credly_cache', 'timeexpires < ?', array(time()));
        mtrace('... used '.microtime_diff($starttime, microtime()) . ' seconds)');
        return true;
    }

    /**
     * Return contents of credly block
     *
     * @return stdClass contents of block
     */
    public function get_content() {
        global $CFG;

        if ($this->content !== null) {
            return $this->content;
        }

        $this->content         = new stdClass;
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

        if (count($badges)) {
            $this->content->text .= html_writer::start_div('credlylatest');
            $this->content->text .= get_string('showinglatest', 'block_credly', $maxentries);
            $this->content->text .= html_writer::end_div();
        }

        for ($x = 0; $x < $maxentries; $x++) {
            $badge = $badges[$x];
            // Use the thumbnail.
            $badgeimagesrc = str_replace('.png', '_5.png', $badge->image);
            $badgeimagestub = html_writer::img($badgeimagesrc, $badge->title,
                    array('title' => $badge->title, 'class' => 'credly_badge credly_badge_id_'.$badge->id));
            $badgelink = html_writer::link('https://credly.com/credit/'.$badge->id, $badgeimagestub, array('target' => '_blank'));
            $this->content->text .= $badgelink;
        }

        if (count($badges)) {
            $this->content->text .= html_writer::start_div();
            $this->content->text .= html_writer::link($CFG->wwwroot . '/blocks/credly/mybadges.php?courseid='.$this->page->course->id,
        get_string('viewallmy', 'block_credly'));
            $this->content->text .= html_writer::end_div();
        }

        $footeritems = array();
        if ($this->page->course->id !== SITEID && has_capability('block/credly:grantbadge', $this->context)) {
            $footeritems[] = html_writer::link($CFG->wwwroot . '/blocks/credly/grantbadge.php?courseid='.$this->page->course->id,
                    get_string('grantbadge', 'block_credly'));
        }
        if (has_capability('block/credly:managebadge', $this->context)) {
            $footeritems[] = html_writer::link($CFG->wwwroot . '/blocks/credly/managebadges.php?courseid='.$this->page->course->id,
                    get_string('managebadges', 'block_credly'));
        }
        if (count($footeritems) > 0) {
            $this->content->footer = html_writer::alist($footeritems);
        }

        return $this->content;
    }
}