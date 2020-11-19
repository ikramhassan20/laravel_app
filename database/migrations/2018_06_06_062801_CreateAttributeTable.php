<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAttributeTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('attribute', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('app_group_id')->unsigned()->nullable();
            $table->string('code', 100);
            $table->enum('level_type', ['platform', 'custom']);
            $table->string('name', 100);
            $table->string('alias', 100);
            // $table->enum('data_type', ['INT', 'VARCHAR', 'SELECT', 'DATE']);
            $table->enum('data_type', config('enums.migration.attribute.data_type'));
            $table->string('length', 100)->nullable();
            $table->string('source_table_name', 100)->nullable();
            $table->string('value_column', 255)->nullable();
            $table->string('text_column', 255)->nullable();
            $table->string('where_condition', 255)->nullable();
            //$table->enum('attribute_type', ['user', 'action', 'conversion']);
            $table->enum('attribute_type', config('enums.migration.attribute.attribute_type'));
            $table->unsignedInteger('created_by')->default(0);
            $table->unsignedInteger('updated_by')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('app_group_id')->references('id')->on('app_group')
                ->onUpdate('cascade')->onDelete('cascade');

            $table->index('data_type');
            $table->index('attribute_type');
            $table->index('code');
            $table->index('level_type');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('attribute');
    }
}
