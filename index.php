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

require_once('../../config.php');
require_once($CFG->dirroot.'/blocks/course_manager/lib.php');
require_once($CFG->dirroot.'/blocks/course_manager/forms.php');

$transfer = optional_param('transfer', false, PARAM_BOOL);
$courseid = optional_param('course', 0, PARAM_INT);

$baseurl = new moodle_url('/blocks/course_manager/index.php');
$strcoursemanager = get_string('pluginname', 'block_course_manager');

$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('standard');
$PAGE->set_url($baseurl);
$PAGE->set_title("{$COURSE->shortname}: $strcoursemanager");
$PAGE->set_heading($COURSE->fullname);
$PAGE->navbar->add($strcoursemanager);

require_login();
if (isguestuser()) {
    print_error('error');
}

$resultcontent = '';
$redirecturl = new moodle_url($CFG->wwwroot . '/blocks/course_manager/index.php');
$PAGE->navbar->add(get_string('key2', 'block_course_manager'), $baseurl);
if ($transfer) {
    $course = $DB->get_record('course', array('id' => $courseid));
    if (!$course || !is_enrolled(context_course::instance($courseid))) {
        print_error('key12', 'block_course_manager');
    }
    $transferform = new course_manager_transfer_form(null, array('course' => $course));
    if ($transferform->is_cancelled()) {
        redirect($redirecturl);
    } else if ($data = $transferform->get_data()) {
        $new = new stdClass();
        $new->category = $data->coursecategory;
        $new->course = $data->course;
        $DB->insert_record('course_manager_courses', $new);
        redirect($redirecturl);
    }
    $resultcontent = $transferform->render();
} else {
    $coursetable = block_course_manager_courses_table();
    if ($coursetable) {
        $resultcontent = $coursetable;
    }
}

echo $OUTPUT->header();
echo $OUTPUT->heading($strcoursemanager);
$editcontrols = block_course_manager_edit_controls($baseurl);
echo $OUTPUT->render($editcontrols);
echo $resultcontent;
echo $OUTPUT->footer();
