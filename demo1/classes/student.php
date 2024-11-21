<?php

namespace Pemrograman;

require_once __DIR__ . '/../traits/LoggingTrait.php';
require_once 'Person.php';

class Student extends Person {
    use LoggingTrait;

    protected $grade; 

    public function __construct($name, $age, $grade) {
        parent::__construct($name, $age);
        $this->grade = $grade;
    }

    public function getDetails() {
        return "Name: $this->name, Age: $this->age, Grade: $this->grade";
    }

    public function __toString() {
        return $this->getDetails();
    }
}