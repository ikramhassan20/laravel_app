<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCampaignsRateLimitLog extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('campaign_rate_limit_log', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('campaign_id');
            $table->tinyInteger('variant')->nullable();
            $table->longText('payload')->nullable();
            $table->date('schedule_date')->nullable();
            $table->enum('status', config('enums.migration.ratelimit_log.status'));
            $table->timestamps();
            $table->foreign('campaign_id')->references('id')
                ->on('campaign')->onUpdate('cascade')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('campaign_rate_limit_log');
    }
}
