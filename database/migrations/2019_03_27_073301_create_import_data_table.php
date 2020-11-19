<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateImportDataTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('import_data', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('company_id')->unsigned();
            $table->string('actual_file_name', 191);
            $table->string('file_name', 191);
            $table->string('file_size', 50)->nullable();
            $table->string('file_path', 191)->nullable();
            $table->tinyInteger('is_processed')->default(0);
            $table->enum('status', ['Pending', 'Inprogress', 'Failed', 'Complete'])->default('Pending');
            $table->text('reason')->nullable();
            $table->tinyInteger('is_deleted')->default(0);
            $table->tinyInteger('remaining_files')->default(0);
            $table->dateTime('process_date')->nullable();
            $table->integer('created_by')->nullable();
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('import_data');
    }
}
