<?php

namespace App\Exceptions;

use Exception;

class SendErrorException extends Exception
{
    public $title;

    public function __construct(string $message, int $code = 400, string $title = null)
    {
        $this->message = $message;
        $this->code = $code;
        $this->title = $title;
        
        $trace = $this->getTrace();
        if (array_key_exists('0', $trace) && $trace[0]['function'] == 'sendError') {
            $this->line = $trace[0]['line'];
            $this->file = $trace[0]['file'];
        }
    }

    final public function getTitle()
    {
        return $this->title;
    }

    final public function getErrorMessage()
    {
        $out = [
            'message' => $this->message,
        ];
        if ($this->title) $out['title'] = $this->title;
        return $out;
    }
}
