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
 * @package   block_via
 * @copyright  SVIeSolutions <alexandra.dinan@sviesolutions.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class block_via extends block_list {

    public function init() {
        $this->title   = get_string('modulename', 'via');
    }

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

        if (!has_capability('mod/via:manage', $context)) {
            $groupings = groups_get_user_groups($COURSE->id, $USER->id);
        }

        if (get_config('mod_viaassign', 'version')) {
            $vias = $DB->get_records_sql('SELECT v.* FROM {via} v
                                        LEFT JOIN {viaassign_submission} vas ON vas.viaid = v.id
                                        WHERE course = ? AND (vas.id IS null)', (array($COURSE->id)));
        } else {
            $vias = get_all_instances_in_course('via', $COURSE);
        }

        if ($vias) {

            $recordingavailable = false;

            $this->content->items[] = '<div class="heading"><b>'.get_string("recentrecordings", "block_via").'</b></div>';
            $this->content->icons[] = '';

            foreach ($vias as $via) {

                if (isset($groupings) && $via->groupingid != 0) {
                    if (!array_key_exists($via->groupingid, $groupings)) {
                        continue;
                    }
                }
                if (!has_capability('mod/via:manage', $context)) {
                    $playbacks = $DB->get_records_sql('SELECT p.* FROM {via_playbacks} p
                                                LEFT JOIN {via_participants} part ON part.activityid = p.activityid
                                                WHERE p.activityid = ? AND part.activityid = ? AND p.deleted = 0 AND part.userid = ?
                                                ORDER BY p.creationdate asc',
                                                array($via->id, $via->id, $USER->id));
                } else {
                    $playbacks = $DB->get_records_sql('select * from {via_playbacks}
                                                WHERE activityid = ? AND deleted = 0
                                                ORDER BY creationdate asc',
                                                array($via->id));
                }

                if ($playbacks) {

                    $cm = get_coursemodule_from_instance('via', $via->id, null, false, MUST_EXIST);

                    foreach ($playbacks as $playback) {

                        if (($via->recordingmode == 1 && ($via->isreplayallowed ||
                            $playback->accesstype > 0) && (($via->datebegin + (60 * $via->duration)) < time())) ||
                            ($via->recordingmode == 2 && ($via->isreplayallowed ||
                            $playback->accesstype > 0))) {

                            if ($playback->accesstype > 0 ||
                                (has_capability('mod/via:manage', $context) && via_get_is_user_host($USER->id, $via->id))) {

                                $private = ($playback->accesstype == 0) ? "dimmed_text" : "";
                                if ($private) {
                                    $param = '&p=1';
                                } else {
                                    $param = '';
                                }

                                $link = '<span class="event '.$private.'">';
                                $link .= '<img src="' . $CFG->wwwroot . '/mod/via/pix/recording_grey.png"
                                        width="25" height="25" alt="'.get_string('recentrecordings', 'block_via') . '"
                                        style="float:left; margin-bottom:10px;" />';
                                if ($via->activitytype != 4) {
                                    $link .= '<a href="' . $CFG->wwwroot . '/mod/via/view.via.php?id='.$cm->id.'&review=1&playbackid='.$playback->playbackid.$param.'" target="new">';
                                }
                                $link .= $via->name." (".$playback->title . ')';
                                $link .= '</a>';

                                $link .= ' <div class="date dimmed_text" style="padding-left:22px; margin-bottom:10px">
                                        ('.userdate($playback->creationdate).')</div></span>';

                                $this->content->items[] = $link;
                                $this->content->icons[] = '';

                                $recordingavailable = true;
                            }
                        }
                    }
                }
            }

            if (!$recordingavailable) {
                $this->content->items[] = '<div class="event dimmed_text"><i>'.get_string("norecording", "block_via").'</i></div>';
                $this->content->icons[] = '';
            }

            if (has_capability('mod/via:view', $context)) {

                $this->content->items[] = '<hr>';
                $this->content->icons[] = '';

                $this->content->items[] = '<span class="event" style="white-space:nowrap">
                            <img src="' . $CFG->wwwroot . '/mod/via/pix/config_grey.png" width="20" height="20"
                            alt="' . get_string('recentrecordings', 'block_via') . ' style="float:left"" />
                            <a target="configvia" href="' . $CFG->wwwroot . '/mod/via/view.assistant.php?redirect=7"
                            onclick="this.target=\'configvia\';
                            return openpopup(null, {url:\'/mod/via/view.assistant.php?redirect=7\',
                            name:\'configvia\', options:\'menubar=0,location=0,scrollbars,resizable,width=750,height=500\'});">'.
                            get_string("configassist", "block_via").'</a></span>';
                $this->content->icons[] = '';

                if (get_config('via', 'via_technicalassist_url') == null) {
                    $this->content->items[] = '<span class="event">
                            <img src="' . $CFG->wwwroot . '/mod/via/pix/assistance_grey.png" width="20" height="20"
                            alt="' . get_string('recentrecordings', 'block_via') . ' style="float:left"" />
                            <a target="configvia" href="' . $CFG->wwwroot . '/mod/via/view.assistant.php?redirect=6"
                            onclick="this.target=\'configvia\';
                            return openpopup(null, {url:\'/mod/via/view.assistant.php?redirect=6\',
                            name:\'configvia\', options:\'menubar=0,location=0,scrollbars,resizable,width=750,height=400\'});">' .
                            get_string("technicalassist", "block_via").'</a></span>';
                } else {
                    $this->content->items[] = '<span class="event">
                            <img src="' . $CFG->wwwroot . '/mod/via/pix/assistance_grey.png" width="20" height="20"
                            alt="' . get_string('recentrecordings', 'block_via') . ' style="float:left"" />
                            <a target="configvia" href="'.get_config('via', 'via_technicalassist_url').'?redirect=6"
                            onclick="this.target=\'configvia\';
                            return openpopup(null, {url:\''.get_config('via', 'via_technicalassist_url').'?redirect=6\',
                            name:\'configvia\', options:\'menubar=0,location=0,scrollbars,resizable,width=750,height=400\'});">' .
                            get_string("technicalassist", "block_via").'</a></span>';
                }

                $this->content->icons[] = '';

            }
        }

        return $this->content;
    }

    public function applicable_formats() {
        return array('site' => false, 'course' => true);
    }

}
