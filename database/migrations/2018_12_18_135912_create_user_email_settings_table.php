<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUserEmailSettingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_email_settings', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('company_id');
            $table->string('host', 100);
            $table->string('port', 5);
            $table->string('username', 100);
            $table->string('password', 100);
            $table->string('from_name', 100);
            $table->string('from_email', 100);
            $table->enum('encryption', ['ssl', 'tls'])->default('tls');
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('users')
                ->onUpdate('cascade')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('user_email_settings');
    }
}
