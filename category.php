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

$id = required_param('id', PARAM_INT);
$delete = optional_param('delete', false, PARAM_BOOL);
$coursedelete = optional_param('coursedelete', false, PARAM_BOOL);
$courseid = optional_param('course', 0, PARAM_INT);

$baseurl = new moodle_url('/blocks/course_manager/category.php', array('id' => $id));
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
$redirecturl = new moodle_url($CFG->wwwroot . '/blocks/course_manager/category.php', array('id' => $id));
$category = $DB->get_record('course_manager_categories', array('id' => $id));
if ($category && $category->user == $USER->id) { // выбранная категория принадлежит текущему пользователю
    $PAGE->navbar->add($category->name, $baseurl);
    if ($delete) {
        if (data_submitted() && confirm_sesskey()) {
            $DB->delete_records('course_manager_courses', array('category' => $id));
            $DB->delete_records('course_manager_categories', array('id' => $id));
            $redirecturl = new moodle_url($CFG->wwwroot . '/blocks/course_manager/index.php');
            redirect($redirecturl);
        } else {
            $continueurl = new moodle_url($CFG->wwwroot . '/blocks/course_manager/category.php', array('id' => $id, 'delete' => true));
            $resultcontent = $OUTPUT->confirm(get_string('key14', 'block_course_manager'), $continueurl, $redirecturl);
        }
    } else if ($coursedelete && data_submitted() && confirm_sesskey()) {
        $DB->delete_records('course_manager_courses', array('category' => $id, 'course' => $courseid));
        redirect($redirecturl);
    } else {
        $coursecattable = block_course_manager_coursecats_table($id);
        if ($coursecattable) {
            $resultcontent = $coursecattable;
        }
        $deleteurl = new moodle_url($CFG->wwwroot . '/blocks/course_manager/category.php', array('id' => $id, 'delete' => true));
        $resultcontent .= $OUTPUT->single_button($deleteurl, get_string('key4', 'block_course_manager'), 'get');
    }
} else {
    print_error('error');
}

echo $OUTPUT->header();
echo $OUTPUT->heading($strcoursemanager);
$editcontrols = block_course_manager_edit_controls($baseurl);
echo $OUTPUT->render($editcontrols);
echo $resultcontent;
echo $OUTPUT->footer();
