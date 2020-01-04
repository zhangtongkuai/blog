<?php
namespace App\BM\email;

class Data {
    public $from;
    public $to;
    public $content;
    public $title;
    /**
     * JobData constructor.
     * @param $to
     * @param $from
     * @param $content
     */
    public function __construct($to, $title, $content, $from = "dev@dongyue.tech")
    {
        $this->from = $from;
        $this->to = $to;
        $this->title = $title;
        $this->content = $content;
    }
}