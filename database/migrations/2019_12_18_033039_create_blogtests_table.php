<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateBlogtestsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('blogtests', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name')->comment('姓名');

            $table->tinyInteger('age')->comment('年龄');
            $table->tinyInteger('avatar')->comment('头像');
            $table->string('description')->comment('个人描述');
            $table->tinyInteger('gender')->default('1')->comment('1是男人，2是女人');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('blogtests');
    }
}
