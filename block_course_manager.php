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
 * block_course_manager
 *
 * @package    block_course_manager
 * @copyright  2017 Aleksandr Raetskiy <ksenon3@mail.ru>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

class block_course_manager extends block_list {

    function init() {
        $this->title = get_string('pluginname', 'block_course_manager');
    }

    function instance_allow_multiple() {
        return false;
    }

    function instance_allow_config() {
        return false;
    }

    function has_config() {
        return false;
    }

    function get_content() {
        global $CFG;

        if (!isloggedin() || isguestuser()) {
            $this->content = '';
            return $this->content;
        }

        if ($this->content !== null) {
            return $this->content;
        }

        $this->content = new stdClass();
        $this->content->items = array();
        $this->content->icons = array();
        $this->content->footer = '';

        $this->content->items[] = html_writer::tag('a', get_string('key1', 'block_course_manager'), array('href' => $CFG->wwwroot.'/blocks/course_manager/index.php'));

        return $this->content;
    }

}
