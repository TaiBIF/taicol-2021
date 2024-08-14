<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('import_usage_logs', function (Blueprint $table) {
            //
            $table->json('old_value')->nullable();
            $table->json('new_value')->nullable();
            $table->text('columns')->nullable();
            $table->unsignedInteger('reference_usage_id')->nullable();
            $table->foreign('reference_usage_id')->references('id')->on('reference_usages');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('import_usage_logs', function (Blueprint $table) {
            //
        });
    }
};
