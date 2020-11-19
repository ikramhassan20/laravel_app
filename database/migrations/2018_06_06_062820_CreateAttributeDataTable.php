<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAttributeDataTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('app_user', function (Blueprint $table) {
            $table->unsignedBigInteger('row_id', true);
            $table->unsignedInteger('app_group_id');
            $table->unsignedInteger('company_id');
            $table->unsignedInteger('user_id');
            $table->string('app_id', 255)->nullable();
            $table->string('username', 255)->nullable();
            $table->string('firstname', 255)->nullable();
            $table->string('lastname', 255)->nullable();
            $table->string('email', 100)->nullable();
            $table->string('image_url', 255)->nullable();
            $table->string('timezone', 400)->nullable();
            $table->double('latitude')->nullable();
            $table->double('longitude')->nullable();
            $table->string('country', 255)->nullable();
            $table->timestamp('last_login')->default(\DB::raw("CURRENT_TIMESTAMP"));
            $table->boolean('enabled')->default(true);
            $table->boolean('enable_notification')->default(true);
            $table->boolean('email_notification')->default(true);
            $table->tinyInteger('status')->default(1);
            $table->tinyInteger('is_deleted')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('company_id')->references('id')->on('users')
                ->onUpdate('cascade')->onDelete('cascade');
            $table->unique(['company_id', 'app_group_id', 'user_id']);
            $table->index(['row_id', 'company_id', 'user_id', 'email'], 'CLUSTER_IDX');

            $table->index('app_id');
            $table->index('status');
            $table->index(['enable_notification', 'email_notification']);
            $table->index('enabled');
            $table->index('last_login');
        });

        Schema::create('app_user_token', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedBigInteger('row_id');
            $table->unsignedInteger('user_id');
            $table->string('app_name', 255)->nullable();
            $table->string('app_id', 255)->nullable();
            $table->string('app_version', 50)->nullable();
            $table->string('app_build', 50)->nullable();
            $table->string('instance_id', 255)->nullable();
            $table->string('user_token', 400)->nullable();
            $table->string('device_token', 400)->nullable();
            $table->enum('device_type', config('engagement.api.notifications.device_types'));
            $table->string('lang', 10)->nullable();
            $table->boolean('is_logged_in')->default(false);
            $table->boolean('is_revoked')->default(false);
            $table->tinyInteger('status')->default(1);
            $table->tinyInteger('is_cache_sync')->nullable()->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('row_id')->references('row_id')->on('app_user')
                ->onUpdate('cascade')->onDelete('cascade');

            $table->index('user_id');
            $table->index('app_name');
            $table->index('app_id');
            $table->index('status');
            $table->index('lang');
            $table->index('device_token');
            $table->index('is_revoked');
        });

        Schema::create('attribute_data', function (Blueprint $table) {
            $table->increments('id')->unsigned();
            $table->integer('company_id')->unsigned();
            $table->integer('row_id')->unsigned();
            $table->string('code', 255);
            $table->text('value');
            $table->enum('data_type', config('enums.migration.attribute.attribute_type'));
            $table->unsignedInteger('created_by')->default(0);
            $table->unsignedInteger('updated_by')->default(0);
            $table->timestamps();

            $table->index('row_id');
            $table->index('code');
            $table->index('data_type');
        });

        \Illuminate\Support\Facades\DB::unprepared('ALTER TABLE `attribute_data` DROP PRIMARY KEY, ADD PRIMARY KEY(id,company_id);');
        \Illuminate\Support\Facades\DB::unprepared('ALTER TABLE `attribute_data` PARTITION BY KEY (id,company_id) PARTITIONS 4;');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('attribute_data');
        Schema::dropIfExists('app_user_token');
        Schema::dropIfExists('app_user');
    }
}
