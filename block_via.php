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
 * Via block.
 *
 * @subpackage block_via
 * @copyright 2011 - 2016 SVIeSolutions
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Via block.
 * Extends moodle class block_list.
 */
class block_via extends block_list {

    /**
     * This is method init
     *
     * @return string This is the bloc title
     *
     */
    public function init() {
        $this->title   = get_string('pluginname', 'via');
    }

    /**
     * Creates the blocks main content
     *
     * @return string
     */
    public function get_content() {
        global $CFG, $USER, $COURSE, $DB;

        require_once($CFG->dirroot . '/mod/via/lib.php');

        if ($this->content !== null) {
            return $this->content;
        }
        $this->content        = new stdClass;
        $this->content->items = array();
        $this->content->icons = array();
        $this->content->footer = '';

        if (!isloggedin() || empty($this->instance)) {
            return $this->content;
        }

        $context = context_course::instance($COURSE->id);

        if (has_capability('mod/via:view', $context)) {

            if ($vias = get_all_instances_in_course('via', $COURSE)) {

                $recordingavailable = false;
                $teachermessage = false;

                $this->content->items[] = '<div class="heading"><b>'.get_string("recentrecordings", "block_via").'</b></div>';
                $this->content->icons[] = '';

                foreach ($vias as $via) {

                    if (!has_capability('mod/via:manage', $context)) {
                        // We need to validate if the use is associated to the activity in order to see the recording.
                        $allowed = $DB->get_record('via_participants', array('activityid' => $via->id, 'userid' => $USER->id));
                        $param = '';
                    } else {
                        // Those with greater rights can see the recording, even if they are not associated!
                        $param = '&fa=1';
                    }

                    if (isset($allowed) && $allowed == true || has_capability('mod/via:manage', $context)) {
                        $playbacks = $DB->get_records_sql('SELECT * from {via_playbacks}
                                                    WHERE activityid = ? AND accesstype <> 0 ORDER BY creationdate asc',
                                                    array($via->id));
                    }
                    if (has_capability('mod/via:manage', $context) && !$teachermessage) {
                        $hiddenplaybacks = $DB->count_records('via_playbacks', array('activityid' => $via->id, 'accesstype' => 0));
                        if ($hiddenplaybacks > 0) {
                            $teachermessage = true;
                        }
                    }

                    if ($playbacks) {
                        foreach ($playbacks as $playback) {

                            $link = '<span class="event ">';
                            $link .= '<img src="' . $CFG->wwwroot . '/mod/via/pix/recording_grey.png"
                                    class="via icon recording" alt="'.
                                    get_string('recentrecordings', 'block_via').'" />
                                    <a href="' . $CFG->wwwroot . '/mod/via/view.via.php?id='.$via->coursemodule.
                                    '&review=1&playbackid='.$playback->playbackid.$param.'" target="_blank">'.
                                    $via->name." (".$playback->title . ')</a>';

                            $link .= ' <div class="date dimmed_text">
                                    ('.userdate($playback->creationdate).')</div></span>';

                            $this->content->items[] = $link;
                            $this->content->icons[] = '';

                            $recordingavailable = true;
                        }
                    }
                }

                if (!$recordingavailable) {
                    $this->content->items[] = '<div class="event dimmed_text"><i>'.
                                            get_string("norecording", "block_via").'</i></div>';
                    $this->content->icons[] = '';
                }
                if (has_capability('mod/via:manage', $context) && $teachermessage) {
                    $this->content->items[] = '<div class="event dimmed_text"><i>'.
                        get_string("hiddenrecordings", "block_via").'</i></div>';
                    $this->content->icons[] = '';
                }

                $this->content->items[] = '<hr>';
                $this->content->icons[] = '';

                $this->content->items[] = '<span class="event">
                                    <img src="' . $CFG->wwwroot . '/mod/via/pix/config_grey.png" class="via icon config"
                                    alt="'. get_string('recentrecordings', 'block_via') . '" />
                                    <a target="configvia" href="' . $CFG->wwwroot .
                                    '/mod/via/view.assistant.php?redirect=7" onclick="this.target=\'configvia\';
                                    return window.open(\'/mod/via/view.assistant.php?redirect=7\', \'configvia\',
                                   \'menubar=0,location=0,scrollbars,resizable,width=740,height=500\');">'.
                                    get_string("configassist", "block_via").'</a></span>';
                $this->content->icons[] = '';

                if (get_config('via', 'via_technicalassist_url') == null) {
                    $this->content->items[] = '<span class="event">
                                        <img src="' . $CFG->wwwroot .'/mod/via/pix/assistance_grey.png" class="via icon tech"
                                        alt="' . get_string('recentrecordings', 'block_via') . '"/>
                                        <a target="configvia"
                                        href="' . $CFG->wwwroot .
                                        '/mod/via/view.assistant.php?redirect=6" onclick="this.target=\'configvia\';
                                        return window.open(\'/mod/via/view.assistant.php?redirect=6\',
                                        \'configvia\',
                                        \'menubar=0,location=0,scrollbars,resizable,width=650,height=400\');">'.
                                        get_string("technicalassist", "block_via").'</a></span>';
                } else {
                    $this->content->items[] = '<span class="event">
                                        <img src="' . $CFG->wwwroot .'/mod/via/pix/assistance_grey.png" class="via icon ass"
                                        alt="' . get_string('recentrecordings', 'block_via') . '" />
                                        <a target="configvia"
                                        href="'. get_config('via', 'via_technicalassist_url').
                        '                ?redirect=6" onclick="this.target=\'configvia\';
                                        return open.window(\''.
                                        get_config('via', 'via_technicalassist_url').'?redirect=6\',\'configvia\',
                                        \'menubar=0,location=0,scrollbars,resizable,width=650,height=400\');">'.
                                        get_string("technicalassist", "block_via").'</a></span>';
                }

                $this->content->icons[] = '';

            }
        }

        return $this->content;
    }

    /**
     * Set the applicable formats for this block to all
     * @return array
     */
    public function applicable_formats() {
        return array('course-view' => true, 'mod' => true, 'admin' => false, 'site-index' => false, 'my' => false);
    }
}
