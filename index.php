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

$id = optional_param('id', 0, PARAM_INT);
$params = array('id' => $id);
if ($id === 0) {
    $params['transfer'] = optional_param('transfer', false, PARAM_BOOL);
    if ($params['transfer'] == true) {
        $params['course'] = required_param('course', PARAM_INT);
    }
}
if ($id > 0) {
    $params['delete'] = optional_param('delete', false, PARAM_BOOL);
    $params['coursedelete'] = optional_param('coursedelete', false, PARAM_BOOL);
    if ($params['coursedelete'] == true) {
        $params['course'] = required_param('course', PARAM_INT);
    }
}
$baseurl = new moodle_url('/blocks/course_manager/index.php', $params);

$strcoursemanager = get_string('pluginname', 'block_course_manager');

$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('standard');
$PAGE->set_url('/blocks/course_manager/index.php', array('category' => $id));
$PAGE->set_title($strcoursemanager);
$PAGE->set_heading($COURSE->fullname);
$PAGE->navbar->add($strcoursemanager, $baseurl);

require_login();
if (isguestuser()) {
    print_error('error');
}

$resultcontent = '';
$redirecturl = new moodle_url($CFG->wwwroot . '/blocks/course_manager/index.php');

if ($id > 0) { // работа с категориями
    $category = $DB->get_record('course_manager_categories', array('id' => $id));
    if ($category && $category->user == $USER->id) { // выбранная категория принадлежит текущему пользователю
        $PAGE->navbar->add($category->name, $baseurl);
        if (data_submitted() && confirm_sesskey()) {
            if ($baseurl->param('delete')) {
                $DB->delete_records('course_manager_courses', array('category' => $id));
                $DB->delete_records('course_manager_categories', array('id' => $id));
            } else if ($baseurl->param('coursedelete')) {
                $DB->delete_records('course_manager_courses', array('category' => $id, 'course' => $baseurl->param('course')));
                $redirecturl->param('id', $id);
                redirect($redirecturl);
            }
            redirect($redirecturl);
        } else {
            $data = array();
            $coursecats = $DB->get_records('course_manager_courses', array('category' => $id));
            foreach ($coursecats as $coursecat) {
                /* проверка, что курс существует и пользователь на него подписан */
                $course = $DB->get_record('course', array('id' => $coursecat->course));
                if (!$course) { // если курс был удален
                    $DB->delete_records('course_manager_courses', array('course' => $coursecat->course)); // удаляем ВСЕ записи с данным курсом
                    continue;
                } else if (!is_enrolled(context_course::instance($coursecat->course))) { // если пользователь был отписан от курса
                    $DB->delete_records('course_manager_courses', array('course' => $coursecat->course, 'category' => $category->id)); // удаляем курс в текущей категории
                    continue;
                }

                $line = array();
                $coursename = $OUTPUT->pix_icon('i/course', null, '', array('class' => 'icon')) . $course->fullname;
                $courseurl = "$CFG->wwwroot/course/view.php?id=$coursecat->course";
                $content = html_writer::link($courseurl, $coursename);
                $line[] = $OUTPUT->heading($content, 4);

                $deleteurl = new moodle_url($CFG->wwwroot . '/blocks/course_manager/index.php', array('id' => $id, 'coursedelete' => true, 'course' => $coursecat->course));
                $line[] = $OUTPUT->single_button($deleteurl, get_string('key10', 'block_course_manager'), 'post');

                $data[] = $row = new html_table_row($line);
                if (!$course->visible) {
                    $row->attributes['class'] = 'dimmed_text';
                }
            }
            if (count($data) > 0) {
                $table = new html_table();
                $table->head = array('', '');
                $table->attributes['class'] = 'generaltable';
                $table->data = $data;
                $resultcontent = html_writer::table($table);
            }
            $deleteurl = new moodle_url($CFG->wwwroot . '/blocks/course_manager/index.php', array('id' => $id, 'delete' => true));
            $resultcontent .= $OUTPUT->single_button($deleteurl, get_string('key4', 'block_course_manager'), 'post');
        }
    } else {
        print_error('error');
    }
} else if ($id == 0) { // работа со списком всех курсов
    $PAGE->navbar->add(get_string('key2', 'block_course_manager'), $baseurl);
    if ($baseurl->param('transfer')) {
        $transferform = new course_manager_transfer_form(null, array('course' => $baseurl->param('course')));
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
        $data = array();
        $courses = enrol_get_users_courses($USER->id, false, 'id, fullname, visible', 'visible DESC, fullname ASC');
        foreach ($courses as $course) {
            $line = array();
            $coursename = $OUTPUT->pix_icon('i/course', null, '', array('class' => 'icon')) . $course->fullname;
            $courseurl = "$CFG->wwwroot/course/view.php?id=$course->id";
            $content = html_writer::link($courseurl, $coursename);
            $line[] = $OUTPUT->heading($content, 4);

            $transferurl = new moodle_url($CFG->wwwroot . '/blocks/course_manager/index.php', array('id' => $id, 'transfer' => true, 'course' => $course->id));
            $line[] = $OUTPUT->single_button($transferurl, get_string('key6', 'block_course_manager'), 'get');

            $data[] = $row = new html_table_row($line);
            if (!$course->visible) {
                $row->attributes['class'] = 'dimmed_text';
            }
        }
        if (count($data) > 0) {
            $table = new html_table();
            $table->head = array('', '');
            $table->attributes['class'] = 'generaltable';
            $table->data = $data;
            $resultcontent = html_writer::table($table);
        }
    }
} else { // добавление категории
    $PAGE->navbar->add(get_string('key3', 'block_course_manager'), $baseurl);
    $addcatform = new course_manager_add_category_form();
    if ($addcatform->is_cancelled()) {
        redirect($redirecturl);
    } else if ($data = $addcatform->get_data()) {
        $newcat = new stdClass();
        $newcat->name = $data->name;
        $newcat->user = $USER->id;
        $newcat->id = $DB->insert_record('course_manager_categories', $newcat);
        $redirecturl->param('id', $newcat->id);
        redirect($redirecturl);
    }
    $resultcontent = $addcatform->render();
}

echo $OUTPUT->header();
echo $OUTPUT->heading($strcoursemanager);
$editcontrols = block_course_manager_edit_controls($baseurl);
echo $OUTPUT->render($editcontrols);
echo $resultcontent;
echo $OUTPUT->footer();
