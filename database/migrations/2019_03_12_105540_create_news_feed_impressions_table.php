<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateNewsFeedImpressionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('news_feed_impression', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('row_id')->unsigned()->nullable();
            $table->integer('user_id')->unsigned()->nullable();
            $table->integer('news_feed_id')->unsigned()->nullable();
            $table->integer('location_id')->unsigned()->nullable();
//            $table->string('platform', 20)->nullable();
            $table->enum('platform', ['android', 'ios', 'web'])->default('ios');
            $table->boolean('viewed')->default(0);
            $table->timestamp('created_date')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('news_feed_impression', function (Blueprint $table) {
            //
        });
    }
}
