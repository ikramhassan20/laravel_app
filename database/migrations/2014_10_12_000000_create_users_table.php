<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->increments('id');
            $table->string('company_key', 255)->nullable();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->string('logo', 255)->nullable();
            $table->string('timezone', 100)->nullable();
            $table->integer('attribute_limit')->default(0);
            $table->boolean('is_active')->default(true);
            $table->rememberToken();
            $table->text('api_token')->nullable();
            $table->enum('cache_status', config('enums.migration.user.cache_status'))->default('completed');
            $table->integer('created_by')->nullable();
            $table->integer('updated_by')->nullable();
            $table->timestamp('last_login')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('is_active');
            $table->index('cache_status');
            $table->index('company_key');

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('users');
    }
}
