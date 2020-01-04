<?php
namespace App\BM\redis;

use Redis;

/**
 * RedisDoc http://redisdoc.com/
 */

class ALRedis {
    public static function pipeline(array $tasks) {
		$res = Redis::pipeline(function($pipe) use ($tasks){
			foreach($tasks as $task){
				$command = $task->command;
				$pipe->$command(...$task->data);
			}
		});
		return $res;
	}

	public static function transaction(array $tasks) {
		Redis::multi();
		foreach($tasks as $task){
			$command = $task->command;
			Redis::$command(...$task->data);
		}
		$res = Redis::exec();
		return $res;
	}

	public static function runCmd(string $cmd, ...$data) {
		$res = Redis::$cmd(...$data);
		return $res;
	}
}