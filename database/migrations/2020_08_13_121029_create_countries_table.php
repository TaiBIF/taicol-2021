<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCountriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('countries', function (Blueprint $table) {
            $table->unsignedSmallInteger('numeric_code')->unique();
            $table->char('alpha-2_code', 2)->unique();
            $table->char('alpha-3_code', 3)->unique();
            $table->json('display');
            $table->unsignedTinyInteger('is_independent');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('countries');
    }
}
