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

require_once($CFG->libdir . '/formslib.php');

class course_manager_transfer_form extends moodleform {

    public function definition() {
        global $CFG;

        $mform = & $this->_form;
        $courseid = $this->_customdata['course'];

        $mform->addElement('hidden', 'id', 0); // признак режима работы со списком курсов
        $mform->setType('id', PARAM_INT);

        $mform->addElement('hidden', 'transfer', true); // признак добавления курса в категорию
        $mform->setType('transfer', PARAM_BOOL);

        $mform->addElement('hidden', 'course', $courseid);
        $mform->setType('course', PARAM_INT);

        $course = get_course($courseid);
        $courseurl = "$CFG->wwwroot/course/view.php?id=$course->id";
        $content = html_writer::link($courseurl, $course->fullname);
        $mform->addElement('static', 'coursename', get_string('key9', 'block_course_manager'), $content);
        
        $options = $this->get_category_options($courseid);
        $mform->addElement('select', 'coursecategory', get_string('key7', 'block_course_manager'), $options);

        $this->add_action_buttons();
    }

    public function validation($data, $files) {
        global $DB, $USER;

        $errors = parent::validation($data, $files);

        $course = $DB->get_record('course', array('id' => $data['course']));
        if (!$course || !is_enrolled(context_course::instance($course->id))) {
            $errors['coursename'] = get_string('key12', 'block_course_manager');
        }
        $category = $DB->get_record('course_manager_categories', array('id' => $data['coursecategory'], 'user' => $USER->id));
        if (!$category) {
            $errors['coursecategory'] = get_string('key13', 'block_course_manager');
        }

        return $errors;
    }

    protected function get_category_options($courseid) {
        global $DB, $USER;
        $options = array();
        $options[0] = get_string('key11', 'block_course_manager');
        $sql = "SELECT cat.id AS id, cat.name AS name
                  FROM {course_manager_categories} cat
             LEFT JOIN {course_manager_courses} c 
                    ON (c.category = cat.id AND c.course = ?)
                 WHERE cat.user = ? AND c.id IS NULL";
        $categories = $DB->get_records_sql($sql, array($courseid, $USER->id));
        foreach ($categories as $category) {
            $options[$category->id] = $category->name;
        }
        return $options;
    }

}

class course_manager_add_category_form extends moodleform {

    public function definition() {
        $mform = $this->_form;

        $mform->addElement('hidden', 'id', -1); // признак режима добавления категории
        $mform->setType('id', PARAM_INT);

        $mform->addElement('text', 'name', get_string('key7', 'block_course_manager'), 'maxlength="250" size="50"');
        $mform->addRule('name', get_string('required'), 'required', null, 'client');
        $mform->setType('name', PARAM_TEXT);

        $this->add_action_buttons();
    }

    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        $name = trim($data['name']);
        if ($name === '') {
            $errors['name'] = get_string('key8', 'block_course_manager');
        }

        return $errors;
    }

}
