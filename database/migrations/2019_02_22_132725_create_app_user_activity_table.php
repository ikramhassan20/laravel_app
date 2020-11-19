<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAppUserActivityTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('app_user_activity', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('row_id')->nullable();
            $table->integer('campaign_id')->nullable();
            $table->string('campaign_code', 40)->nullable();
            $table->enum('resource_type', ['campaign','board'])->nullable()->default('campaign');
            $table->string('track_key', 40)->nullable();

            $table->string('event_id',255)->nullable();
            $table->string('event_value', 100)->nullable();
            $table->enum('device_type', ['IOS','ANDROID','WEB'])->nullable();
            $table->string('build', 50)->nullable();

            $table->string('version', 50)->nullable();
            $table->integer('app_id')->nullable();
            $table->enum('rec_type', ['conversion','action_trigger','api_trigger'])->nullable();
            $table->timestamps();

            $table->index(['campaign_id', 'rec_type', 'track_key'], 'CLUSTER_USER_CAMPAIGN_IDX');
            $table->index('event_id');
            $table->index('build');
            $table->index('version');
            $table->index('app_id');
            $table->index('row_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('app_user_activity');
    }
}
