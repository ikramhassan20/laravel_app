<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateNewsFeedTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('news_feed', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('app_group_id');
            $table->unsignedInteger('segment_id')->nullable();
            $table->unsignedInteger('location_id')->nullable();
            $table->string('news_feed_template_id', 255)->nullable();
            $table->string('name', 255)->nullable();
            $table->text('tags')->nullable();
            $table->text('links')->nullable();
            $table->enum('category', config('enums.migration.newsfeed.category'))->nullable();
            $table->enum('step', config('enums.migration.newsfeed.step'));
            $table->enum('status', config('enums.migration.newsfeed.status'));

            $table->timestamp('start_time')->nullable();
            $table->timestamp('end_time')->nullable();
            $table->unsignedInteger('created_by')->default(0);
            $table->unsignedInteger('updated_by')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('app_group_id')->references('id')->on('app_group')
                ->onUpdate('cascade')->onDelete('cascade');
            $table->foreign('segment_id')->references('id')->on('segment')
                ->onUpdate('cascade')->onDelete('cascade');
            $table->foreign('location_id')->references('id')->on('location')
                ->onUpdate('cascade')->onDelete('cascade');
//            $table->foreign('news_feed_template_id')->references('id')->on('news_feed_templates')
//                ->onUpdate('cascade')->onDelete('cascade');

            $table->index('status');
            $table->index('step');

        });

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('news_feed');
    }
}
