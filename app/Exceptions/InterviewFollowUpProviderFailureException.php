<?php

namespace App\Exceptions;

use RuntimeException;

final class InterviewFollowUpProviderFailureException extends RuntimeException
{
    public function __construct(string $message = 'The AI interviewer could not prepare the next question. Please try again.')
    {
        parent::__construct($message);
    }
}
