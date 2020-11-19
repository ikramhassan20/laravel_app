<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSegmentTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('segment', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('app_group_id');
            $table->string('name', 100);
            $table->text('tags');
            $table->text('criteria');
            $table->text('rules')->nullable();
            $table->string('attribute_fields', 400)->nullable();
            $table->string('action_fields', 400)->nullable();
            $table->string('conversion_fields', 400)->nullable();
            // $table->enum('type', config('engagement.segments.types'))->default('user');
            $table->unsignedInteger('created_by');
            $table->unsignedInteger('updated_by');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('app_group_id')->references('id')->on('app_group')
                ->onUpdate('cascade')->onDelete('cascade');

            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('segment');
    }
}
