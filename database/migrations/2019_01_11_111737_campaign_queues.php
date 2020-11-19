<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CampaignQueues extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('campaign_queue', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('campaign_id');
            //$table->enum('status', []);
            $table->enum('status', config('enums.migration.campaign_queue.status'));
           // $table->enum('priority', [1, 2, 3]);
            $table->enum('priority', config('enums.migration.campaign_queue.priority'));

            $table->text('details')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('start_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

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
        Schema::dropIfExists('campaign_queue');
    }
}
