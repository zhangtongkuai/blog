<?php
namespace App\BM\redis;

class ALRedisTask {
    var $command;
	var $data;

	public function __construct(string $command, ...$data)
	{
		$this->command = $command;
		$this->data = $data;
    }

    public static function new(string $command, ...$data) : ALRedisTask {
        return new ALRedisTask($command, ...$data);
    }
}