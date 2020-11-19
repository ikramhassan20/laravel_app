<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCampaignsVariantsTunnelsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('campaign_variant_tunnel', function (Blueprint $table) {
            $table->increments('id');
            $table->string('distribution_value', 255)->nullable();
            $table->integer('variant_id')->nullable(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index('variant_id');

//            $table->foreign('campaign_id')->references('id')
//                ->on('campaign')->onUpdate('cascade')->onDelete('cascade');

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('campaign_variant_tunnel');
    }
}
