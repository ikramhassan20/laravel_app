<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCampaignCapRulesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('campaign_cap_rules', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('app_group_id');
            $table->unsignedInteger('cap_limit')->default(0);
            $table->enum('campaign_type', ['Push', 'InApp', 'Email'])->default('Push');
            $table->enum('duration_unit', ['minutes', 'days', 'weeks'])->default('minutes');
            $table->unsignedInteger('duration_value')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('app_group_id')->references('id')->on('app_group')
                ->onUpdate('cascade')->onDelete('cascade');

            $table->index(['app_group_id', 'campaign_type'], 'CAMPAIGN_CAP_RULES_INDEX');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('campaign_cap_rules');
    }
}
