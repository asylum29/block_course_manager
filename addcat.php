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

$baseurl = new moodle_url('/blocks/course_manager/addcat.php');
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

$redirecturl = new moodle_url($CFG->wwwroot . '/blocks/course_manager/index.php');
$PAGE->navbar->add(get_string('key3', 'block_course_manager'), $baseurl);
$addcatform = new course_manager_add_category_form();
if ($addcatform->is_cancelled()) {
    redirect($redirecturl);
} else if ($data = $addcatform->get_data()) {
    $newcat = new stdClass();
    $newcat->name = trim($data->name);
    $newcat->user = $USER->id;
    $DB->insert_record('course_manager_categories', $newcat);
    redirect($redirecturl);
}

echo $OUTPUT->header();
echo $OUTPUT->heading($strcoursemanager);
$editcontrols = block_course_manager_edit_controls($baseurl);
echo $OUTPUT->render($editcontrols);
$addcatform->display();
echo $OUTPUT->footer();
