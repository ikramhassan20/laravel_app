<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCampaignsRateLimit extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('campaign_rate_limit', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('campaign_id');
            $table->integer('rate_limit');
            $table->integer('duration_value');
            $table->enum('duration_unit', config('enums.migration.ratelimit.duration_unit'));
            $table->timestamps();
            $table->softDeletes();
            $table->foreign('campaign_id')->references('id')
                ->on('campaign')->onUpdate('cascade')->onDelete('cascade');

            $table->index('duration_unit');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('campaign_rate_limit');
    }
}
