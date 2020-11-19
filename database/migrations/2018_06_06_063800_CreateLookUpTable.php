<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateLookUpTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('lookup', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('app_group_id');
            $table->string('code', 100);
            $table->string('name', 100);
            $table->text('description')->nullable();
            $table->unsignedInteger('parent_id');
            $table->enum('level', config('enums.migration.lookup.level'))->default('platform');
            //$table->enum('level', ['platform', 'company'])->default('platform');
            $table->unsignedInteger('created_by')->default(1);
            $table->unsignedInteger('updated_by')->default(1);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('app_group_id')->references('id')->on('app_group')
                ->onUpdate('cascade')->onDelete('cascade');

            $table->index('code');
            $table->index('level');

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('lookup');
    }
}
