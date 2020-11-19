<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateExpiredBoardTrackingTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('expired_board_tracking', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('board_id');
            $table->integer('row_id');
            $table->integer('app_user_token_id');
            $table->integer('variant_step_id');
            $table->integer('language_id')->default('1');
            $table->string('email');
            $table->text('firebase_key');
            $table->string('device_key');
            $table->enum('device_type', ['android', 'ios'])->default('android');
            $table->text('payload')->nullable();
            $table->text('message')->nullable();
            $table->string('track_key');
            $table->string('job');
            $table->enum('status', ['added', 'executing', 'completed', 'failed'])->default('added');
            $table->tinyInteger('sent');
            $table->tinyInteger('viewed');
            $table->timestamp('started_at')->default('2019-01-01 00:00:00');
            $table->timestamp('ended_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('viewed_at')->nullable();
            $table->timestamps();
//            $table->foreign('board_id')->references('id')
//                ->on('board')->onUpdate('cascade')->onDelete('cascade');

            $table->index('row_id', 'BOARD_TRACKING_ROW_ID_INDEX');
            $table->index('variant_step_id', 'BOARD_TRACKING_VARIANT_STEP_ID_INDEX');
            $table->index('email', 'BOARD_TRACKING_EMAIL_INDEX');
            $table->index('device_type', 'BOARD_TRACKING_DEVICE_TYPE_INDEX');
            $table->index('device_key', 'BOARD_TRACKING_DEVICE_KEY_INDEX');
            $table->index('track_key', 'BOARD_TRACKING_TRACK_KEY_INDEX');
            $table->index('status', 'BOARD_TRACKING_STATUS_INDEX');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('expired_board_tracking');
    }
}
