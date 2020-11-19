<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCampaignTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('campaign', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('app_group_id');

            $table->enum('campaign_type', config('enums.migration.campaign.campaign_type'))->nullable();

            $table->string('subject')->nullable();
            $table->string('from_email')->nullable();
            $table->string('from_name')->nullable();
            $table->text('tags')->nullable();
            $table->string('code', 100)->unique()->nullable();
            $table->string('name', 255)->nullable();
            $table->enum('step', config('enums.migration.campaign.step'));


            $table->timestamp('start_time')->nullable();
            $table->timestamp('end_time')->nullable();
            $table->enum('status', config('enums.migration.campaign.status'))->default('draft');
            $table->boolean('is_remove_cache')->default(false);
            $table->enum('schedule_type', config('enums.migration.campaign.schedule_type'))->nullable();
            $table->enum('delivery_type', config('enums.migration.campaign.delivery_type'))->nullable();
            $table->enum('priority', config('enums.migration.campaign.priority'))->default('medium');
            $table->unsignedInteger('action_trigger_delay_value')->nullable();
            $table->enum('action_trigger_delay_unit', config('enums.migration.campaign.action_trigger_delay_unit'))->nullable();

            $table->boolean('delivery_control')->default(false);
            $table->unsignedInteger('delivery_control_delay_value')->nullable();
            $table->enum('delivery_control_delay_unit', config('enums.migration.campaign.delivery_control_delay_unit'))->nullable();

            $table->boolean('capping')->default(false);
            $table->unsignedInteger('created_by')->nullable();
            $table->unsignedInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->foreign('app_group_id')->references('id')->on('app_group')
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
        Schema::dropIfExists('campaign');
    }
}
