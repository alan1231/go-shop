<?php

namespace App;

use Exception;

class ServiceException extends Exception {
    public function __construct(string $message, int $status = 400) {
        parent::__construct($message, $status);
    }
}
