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
            $table->string('last_name', 30);
            $table->string('first_name', 30);
            $table->string('middle_name', 80);
            $table->string('abbreviation_name', 30);
            $table->string('original_full_name');
            $table->string('other_names');
            $table->string('year_birth', 4)->nullable();
            $table->string('year_death', 8)->nullable();
            $table->string('year_publication', 30);
            $table->smallInteger('country_numeric_code')->nullable();
            $table->string('biology_departments');
            $table->string('biological_group', 100);
            $table->unsignedInteger('user_id')->nullable();
            $table->timestamps();
            $table->unique(['last_name', 'first_name', 'middle_name', 'year_birth'], 'persons_names_unique');
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
        Schema::dropIfExists('persons');
    }
}
