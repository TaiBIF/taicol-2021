<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class InitialTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('nomenclatures', function (Blueprint $table) {
            $table->tinyIncrements('id');
            $table->string('name');
            $table->json('display');
            $table->json('settings');
            $table->string('group', 10);
            $table->unsignedTinyInteger('is_disabled')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('ranks', function (Blueprint $table) {
            $table->smallIncrements('id');
            $table->string('key')->unique();
            $table->string('abbreviation');
            $table->json('display');
            $table->unsignedTinyInteger('is_highlight')->default(0);
            $table->unsignedSmallInteger('order')->default(0);
        });

        Schema::create('nomenclature_rank', function (Blueprint $table) {
            $table->smallIncrements('id');
            $table->unsignedSmallInteger('rank_id');
            $table->unsignedTinyInteger('nomenclature_id');
        });

        Schema::create('books', function (Blueprint $table) {
            $table->increments('id');
            $table->string('title')->unique();
            $table->string('title_abbreviation')->default('');
            $table->string('isbn')->default('');
            $table->string('issn_print')->default('');
            $table->string('issn_electronic')->default('');
            $table->string('publisher')->default('');
            $table->unsignedSmallInteger('country_id')->nullable();
            $table->string('city')->default('');
            $table->timestamps();
        });

        Schema::create('book_person', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('person_id');
            $table->unsignedInteger('book_id');
            $table->timestamps();

            $table->foreign('person_id')->references('id')->on('persons');
            $table->foreign('book_id')->references('id')->on('books');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('nomenclatures');
        Schema::drop('persons');
        Schema::drop('ranks');
        Schema::drop('books');
        Schema::drop('book_person');
    }
}
