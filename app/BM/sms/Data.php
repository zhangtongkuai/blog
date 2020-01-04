<?php
namespace App\BM\sms;

class Data {
    public $to;
    public $data;
    public $content;
    public $templates;
    /**
     * @param $to
     * @param $data
     * @param $content
     * @param array $templates
     */
    public function __construct($to, $data, $content, $templates = [])
    {
        $this->to = $to;
        $this->data = $data;
        $this->content = $content;
        $this->templates = $templates;
    }
}