<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateExpiredCampaignStatsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('expired_campaign_stats', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('campaign_id');
            $table->bigInteger('targeted_users')->default(0);

            $table->bigInteger('total_trackings')->default(0);
            $table->bigInteger('total_sent')->default(0);
            $table->bigInteger('total_viewed')->default(0);
            $table->bigInteger('total_unique_viewed')->default(0);
            $table->bigInteger('total_failed')->default(0);

            $table->bigInteger('total_android_sent')->default(0);
            $table->bigInteger('total_android_viewed')->default(0);
            $table->bigInteger('total_android_failed')->default(0);


            $table->bigInteger('total_ios_sent')->default(0);
            $table->bigInteger('total_ios_viewed')->default(0);
            $table->bigInteger('total_ios_failed')->default(0);
            $table->string('last_ten_row_ids')->nullable();

            $table->timestamps();

            $table->foreign('campaign_id')->references('id')->on('campaign')
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
        Schema::dropIfExists('expired_campaign_stats');
    }
}
