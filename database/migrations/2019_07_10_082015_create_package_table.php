<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePackageTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('package', function (Blueprint $table) {
            $table->increments('id');
            $table->string("code", 255)->unique();
            $table->string("name", 255)->unique();
            $table->string("description");
            $table->enum("type", ['monthly', 'yearly']);
            $table->integer("push_limit");
            $table->integer("email_limit");
            $table->integer("inapp_limit");
            $table->integer("nfc_limit");
            $table->boolean('is_active')->default(true);
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
        Schema::dropIfExists('package');
    }
}
