<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCampaignTemplateTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
//        Schema::create('campaign_template', function (Blueprint $table) {
//            $table->increments('id');
//            $table->string('title')->nullable();
//            $table->longText('content')->nullable();
//            $table->enum('type', ['PUSH', 'EMAIL', 'BANNER', 'DIALOG', 'FULL SCREEN']);
//            $table->string('thumbNail');
//            $table->timestamps();
//        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
//        Schema::dropIfExists('campaign_template');
    }
}
