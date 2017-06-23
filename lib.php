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

    $categories = $DB->get_records('course_manager_categories', array('user' => $USER->id));
    foreach ($categories as $category) {
        $tabs[] = new tabobject('view' . $category->id, new moodle_url($viewurl, array('id' => $category->id)), $category->name);
        if ($currenturl->param('id') == $category->id) {
            $currenttab = 'view' . $category->id;
        }
    }

    $tabs[] = new tabobject('addcat', new moodle_url($viewurl, array('id' => -1)), get_string('key3', 'block_course_manager'));
    if ($currenturl->param('id') == -1) {
        $currenttab = 'addcat';
    }

    return new tabtree($tabs, $currenttab);
}
