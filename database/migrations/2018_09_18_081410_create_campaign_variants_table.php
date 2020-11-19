<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCampaignVariantsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('campaign_variant', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('campaign_id');
            $table->tinyInteger('distribution')->nullable();
            $table->unsignedInteger('message_type_id')->nullable();
            $table->unsignedInteger('orientation_id')->nullable();
            $table->unsignedInteger('position_id')->nullable();
            $table->unsignedInteger('platform_id')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('campaign_id')->references('id')->on('campaign')
                ->onUpdate('cascade')->onDelete('cascade');
            $table->foreign('message_type_id')->references('id')->on('lookup')
                ->onUpdate('cascade')->onDelete('cascade');
            $table->foreign('orientation_id')->references('id')->on('lookup')
                ->onUpdate('cascade')->onDelete('cascade');
            $table->foreign('position_id')->references('id')->on('lookup')
                ->onUpdate('cascade')->onDelete('cascade');
            $table->foreign('platform_id')->references('id')->on('lookup')
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
        Schema::dropIfExists('campaign_variant');
    }
}
