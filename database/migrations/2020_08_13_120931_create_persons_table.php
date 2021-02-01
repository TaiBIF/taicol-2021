<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePersonsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('persons', function (Blueprint $table) {
            $table->increments('id');
            $table->string('last_name');
            $table->string('first_name');
            $table->string('middle_name');
            $table->string('abbreviation_name');
            $table->string('original_full_name');
            $table->string('other_names');
            $table->string('year_birth')->nullable();
            $table->string('year_death')->nullable();
            $table->string('year_publication', 30);
            $table->smallInteger('country_numeric_code')->nullable();
            $table->string('biology_departments');
            $table->unsignedInteger('user_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('person');
    }
}
