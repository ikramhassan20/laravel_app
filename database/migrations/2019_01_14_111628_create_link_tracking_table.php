<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateLinkTrackingTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('link_tracking', function (Blueprint $table) {
            $table->increments('id');
            $table->enum('rec_type',config('enums.migration.link_tracking.rec_type'));
            $table->integer('rec_id');
            $table->bigInteger('row_id');
            $table->text('actual_url')->nullable();
            $table->dateTime('created_date');
            $table->string('ip_address');
            $table->text('user_agent');
            $table->enum('device_type', config('enums.migration.link_tracking.device_type'));
            $table->integer('viewed');
            $table->tinyInteger('is_board')->default('0');
            $table->timestamps();
            $table->softDeletes();

            $table->index('rec_type');
            $table->index('rec_id');
            $table->index('row_id');
            $table->index('device_type');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('link_tracking');
    }
}
