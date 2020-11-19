<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCampaignTrackingLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('campaign_tracking_log', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('campaign_tracking_id');
            $table->string('status');
            $table->text('message');
            $table->timestamps();
//            $table->foreign('campaign_tracking_id')->references('id')
//                ->on('campaign_tracking')->onUpdate('cascade')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('campaign_tracking_logs');
    }
}
