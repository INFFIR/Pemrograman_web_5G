<?php

namespace Pemrograman;

trait LoggingTrait {
    public function logAction($message) {
        echo "Log: $message" . PHP_EOL;
    }
}