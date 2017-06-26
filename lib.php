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

function block_course_manager_edit_controls(moodle_url $currenturl) {
    global $DB, $USER;

    $tabs = array();
    $currenttab = 'viewall';
    $viewurl = new moodle_url('/blocks/course_manager/index.php');
    $tabs[] = new tabobject('viewall', new moodle_url($viewurl), get_string('key2', 'block_course_manager'));

    $caturl = new moodle_url('/blocks/course_manager/category.php');
    $categories = $DB->get_records('course_manager_categories', array('user' => $USER->id));
    foreach ($categories as $category) {
        $tabs[] = new tabobject('view' . $category->id, new moodle_url($caturl, array('id' => $category->id)), $category->name);
        if ($currenturl->param('id') == $category->id) {
            $currenttab = 'view' . $category->id;
        }
    }

    $addcat = new moodle_url('/blocks/course_manager/addcat.php');
    $tabs[] = new tabobject('addcat', new moodle_url($addcat), get_string('key3', 'block_course_manager'));
    if ($currenturl->get_path() === $addcat->get_path()) {
        $currenttab = 'addcat';
    }

    return new tabtree($tabs, $currenttab);
}

function block_course_manager_courses_table() {
    global $DB, $USER, $OUTPUT, $CFG;
    
    $data = array();
    $courses = enrol_get_users_courses($USER->id, false, 'id, fullname, visible', 'visible DESC, fullname ASC');
    foreach ($courses as $course) {
        $line = array();
        $coursename = $OUTPUT->pix_icon('i/course', null, '', array('class' => 'icon')) . $course->fullname;
        $courseurl = "$CFG->wwwroot/course/view.php?id=$course->id";
        $content = html_writer::link($courseurl, $coursename);
        $content = $OUTPUT->heading($content, 4);

        $sql = "SELECT cat.id AS id, cat.name AS name
                  FROM {course_manager_categories} cat
                  JOIN {course_manager_courses} c 
                    ON (c.category = cat.id)
                 WHERE c.course = ? AND cat.user = ?";
        $catnames = array();
        foreach($DB->get_records_sql($sql, array($course->id, $USER->id)) as $coursecat) {
            $catnames[] = $coursecat->name;
        }
        if (count($catnames) > 0) {
            $catstring = html_writer::tag('b', get_string('key15', 'block_course_manager') . ':&nbsp;');
            $catstring .= implode(', ', $catnames);
            $content .= html_writer::div($catstring);
        }

        $line[] = $content;

        $transferurl = new moodle_url($CFG->wwwroot . '/blocks/course_manager/index.php', array('transfer' => true, 'course' => $course->id));
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
        return html_writer::table($table);
    }
    return false;
}

function block_course_manager_coursecats_table($category) {
    global $DB, $OUTPUT, $CFG;
    
    $data = array();
    $coursecats = $DB->get_records('course_manager_courses', array('category' => $category));
    foreach ($coursecats as $coursecat) {
        /* проверка, что курс существует и пользователь на него подписан */
        $course = $DB->get_record('course', array('id' => $coursecat->course));
        if (!$course) { // если курс был удален
            $DB->delete_records('course_manager_courses', array('course' => $coursecat->course)); // удаляем ВСЕ записи с данным курсом
            continue;
        } else if (!is_enrolled(context_course::instance($coursecat->course))) { // если пользователь был отписан от курса
            $DB->delete_records('course_manager_courses', array('course' => $coursecat->course, 'category' => $category)); // удаляем курс в текущей категории
            continue;
        }

        $line = array();
        $coursename = $OUTPUT->pix_icon('i/course', null, '', array('class' => 'icon')) . $course->fullname;
        $courseurl = "$CFG->wwwroot/course/view.php?id=$coursecat->course";
        $content = html_writer::link($courseurl, $coursename);
        $line[] = $OUTPUT->heading($content, 4);

        $deleteurl = new moodle_url($CFG->wwwroot . '/blocks/course_manager/category.php', array('id' => $category, 'coursedelete' => true, 'course' => $coursecat->course));
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
        return html_writer::table($table);
    }
    return false;
}
