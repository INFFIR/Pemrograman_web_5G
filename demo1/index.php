<?php

require_once 'classes/Student.php';
require_once 'classes/Teacher.php';

use Pemrograman\Student;
use Pemrograman\Teacher;

$student = new Student("Farriel", 20, grade: "A");
$student1 = new Student("Windah", 21, grade: "C+");
$teacher = new Teacher("Mrs. Nita", 35, "Biology");


$student->logAction("Student accessed.");
$teacher->logAction("Teacher accessed.");

$output = [
    'student' => [
        'name' => $student->name,
        'age' => $student->age,
        'grade' => $student->grade,
    ],
    'student1' => [
        'name' => $student1->name,
        'age' => $student1->age,
        'grade' => $student1->grade,
    ],
    'teacher' => [
        'name' => $teacher->name,
        'age' => $teacher->age,
        'subject' => $teacher->subject,
    ]
];

header('Content-Type: application/json');
echo json_encode($output, JSON_PRETTY_PRINT);

?>