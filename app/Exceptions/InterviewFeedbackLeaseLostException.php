<?php

namespace App\Exceptions;

use RuntimeException;

final class InterviewFeedbackLeaseLostException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('The interview feedback processing lease is no longer active.');
    }
}
