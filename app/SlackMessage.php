<?php

namespace App;

use function GuzzleHttp\json_encode;


class SlackMessage
{
    public $text;
    public $isMarkdown;

    public function __construct($text = null, $isMarkdown = true)
    {
        $this->text = $text;
        $this->isMarkdown = $isMarkdown;
    }

    public function text($text)
    {
        $this->text = $text;
        return $this;
    }

    public function markdown()
    {
        $this->isMarkdown = true;
        return $this;
    }

    public function plainText()
    {
        $this->isMarkdown = false;
        return $this;
    }

    public function toString()
    {
        return $this->__toString();
    }

    public function __toString()
    {
        $message = [
            'text' => $this->text,
            'mrkdwn' => $this->isMarkdown,
        ];

        return json_encode($message);
    }
}
