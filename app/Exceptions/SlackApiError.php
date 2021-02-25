<?php

namespace App\Exceptions;

use Exception;

class SlackApiError extends Exception
{
    protected $data;
    protected $error;

    public function __construct($message = '', $error = null, $args = null, $data = null)
    {
        parent::__construct($message, null, null);

        $this->error = $error;
        $this->args = $args;
        $this->data = $data;
    }

    public function getArgs()
    {
        return $this->args;
    }

    public function getData()
    {
        return $this->data;
    }

    public function getError()
    {
        return $this->error;
    }
}
