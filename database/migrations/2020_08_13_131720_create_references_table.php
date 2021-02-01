<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateReferencesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {

        Schema::create('references', function (Blueprint $table) {
            $table->increments('id');
            $table->string('cover_path');
            $table->unsignedTinyInteger('type');
            $table->string('title')->default('');
            $table->string('subtitle')->default('');
            $table->string('publish_year', 4);
            $table->string('language', 8)->nullable();
            $table->json('properties');
            $table->text('note');
            $table->unsignedTinyInteger('is_publish');
            $table->unsignedInteger('book_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('person_reference', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('person_id');
            $table->unsignedInteger('reference_id');
            $table->timestamps();

            $table->foreign('person_id')->references('id')->on('persons');
            $table->foreign('reference_id')->references('id')->on('references');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('reference');
        Schema::dropIfExists('person_reference');
    }
}
