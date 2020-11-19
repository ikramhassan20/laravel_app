<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateResponseLogTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('response_logs', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('company_id')->unsigned()->nullable();
            $table->integer('record_id')->unsigned()->nullable();
            $table->string('name', 255)->nullable();
            $table->enum('type', ['api', 'console'])->default('console')->nullable();
            $table->enum('console_type', ['other', 'board', 'campaign'])->nullable();
            $table->double('response_time', 11)->comment('response time in milli seconds')->nullable();
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
        Schema::dropIfExists('response_logs');
    }
}
