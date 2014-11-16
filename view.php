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
 * Prints a particular instance of notification
 *
 * You can have a rather longer description of the file as well,
 * if you like, and it can span multiple lines.
 *
 * @package    mod_notification
 * @copyright  2014 Tonis Tartes
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// Replace notification with the name of your module and remove this line.

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once(dirname(__FILE__).'/lib.php');

$id = optional_param('id', 0, PARAM_INT); // Course_module ID, or
$n  = optional_param('n', 0, PARAM_INT);  // ... notification instance ID - it should be named as the first character of the module.

if ($id) {
    $cm         = get_coursemodule_from_id('notification', $id, 0, false, MUST_EXIST);
    $course     = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
    $notification  = $DB->get_record('notification', array('id' => $cm->instance), '*', MUST_EXIST);
} else if ($n) {
    $notification  = $DB->get_record('notification', array('id' => $n), '*', MUST_EXIST);
    $course     = $DB->get_record('course', array('id' => $notification->course), '*', MUST_EXIST);
    $cm         = get_coursemodule_from_instance('notification', $notification->id, $course->id, false, MUST_EXIST);
} else {
    error('You must specify a course_module ID or an instance ID');
}

require_login($course, true, $cm);
$context = context_module::instance($cm->id);

$event = \mod_notification\event\course_module_viewed::create(array(
    'objectid' => $PAGE->cm->instance,
    'context' => $PAGE->context,
));
$event->add_record_snapshot('course', $PAGE->course);
// In the next line you can use $PAGE->activityrecord if you have set it, or skip this line if you don't have a record.
$event->add_record_snapshot($PAGE->cm->modname, $notification);
$event->trigger();

// Print the page header.

$PAGE->set_url('/mod/notification/view.php', array('id' => $cm->id));
$PAGE->set_title(format_string($notification->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($context);

/*
 * Other things you may want to set - remove if not needed.
 * $PAGE->set_cacheable(false);
 * $PAGE->set_focuscontrol('some-html-id');
 * $PAGE->add_body_class('notification-'.$somevar);
 */

// Output starts here.
echo $OUTPUT->header();

// Conditions to show the intro can change to look for own settings or whatever.
if ($notification->intro) {
    echo $OUTPUT->box(format_module_intro('notification', $notification, $cm->id), 'generalbox mod_introbox', 'notificationintro');
}

$sentmails = $DB->get_records('notifications_sent', array('notification' => $notification->id, 'course' => $course->id));

$table = new html_table();
$table->head  = array ('#', 'Username', 'Sent to');
$table->data = array();
$i = 1;
foreach ($sentmails as $key => $value) {
    $username = $DB->get_record('user', array('id' => $value->user), 'username');
    $table->data[] = array($i, '<a href="'.$CFG->wwwroot.'/user/profile.php?id='.$value->user.'" target="_blank">'.$username->username.'</a>', $value->sentto, date('Y H:i:s', $value->timecreated));
    $i++;
}

$table = html_writer::table($table);

echo $OUTPUT->box(html_writer::div($table), 'generalbox mod_introbox notificationbox');

// Finish the page.
echo $OUTPUT->footer();
