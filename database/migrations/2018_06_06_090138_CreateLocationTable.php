<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateLocationTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('location', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('app_group_id');
            $table->string('code', 255);
            $table->string('name', 255);
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('created_by')->default(0);
            $table->unsignedInteger('updated_by')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('app_group_id')->references('id')->on('app_group')
                ->onUpdate('cascade')->onDelete('cascade');

            $table->index('is_active');
            $table->index('code');

        });

        Schema::create('location_areas', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('location_id');
            $table->string('address', 255);
            $table->float('latitude', 10, 6);
            $table->float('longitude', 10, 6);
            $table->string('radius', 255);
            $table->timestamps();
            $table->softDeletes();

            $table->index('location_id');

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('location_areas');
        Schema::dropIfExists('location');
    }
}
