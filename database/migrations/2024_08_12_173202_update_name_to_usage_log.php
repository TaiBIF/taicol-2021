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
            $table->unsignedInteger('taxon_name_id')->nullable();
            $table->foreign('taxon_name_id')->references('id')->on('taxon_names');
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
            $table->dropColumn('taxon_name_id');
        });
    }
};
