<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCampaignActionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('campaign_action', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('campaign_id');
            $table->unsignedInteger('action_id');
            $table->string('value', 250)->nullable();
            $table->enum('action_type', config('enums.migration.campaign_action.action'));
            $table->enum('period', config('enums.migration.campaign_action.period'))->default('day');
            $table->tinyInteger('validity')->default(0);
            // $table->enum('period', ['minute', 'hour', 'day'])->default('day');
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('campaign_id')->references('id')
                ->on('campaign')->onUpdate('cascade')->onDelete('cascade');
            //$table->foreign('action_id')->references('id')
            //   ->on('lookup')->onUpdate('cascade')->onDelete('cascade');

            $table->index('action_id');
            $table->index('action_type');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('campaign_action');
    }
}
