<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateNotificationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('notification', function (Blueprint $table) {
            $table->increments('id');
            $table->string('email')->nullable();
            $table->string('device_token')->nullable();
            $table->longText('payload')->nullable();
            $table->longText('message')->nullable();
            $table->enum('platform', config('engagement.api.notifications.device_types'));
            $table->boolean('sent')->default(false);
            $table->timestamp('sent_at')->nullable();
            $table->boolean('viewed')->default(false);
            $table->timestamp('viewed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('notification_log', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('notification_id')->unsigned();
            $table->char('status', 10);
            $table->text('message');
            $table->timestamps();
            $table->softDeletes();
            $table->foreign('notification_id')->references('id')
                ->on('notification')->onUpdate('cascade')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('notification_log');
        Schema::dropIfExists('notification');
    }
}
