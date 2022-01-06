<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTaxonNamesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('taxon_names', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedSmallInteger('rank_id');
            $table->unsignedTinyInteger('nomenclature_id');
            $table->unsignedTinyInteger('reference_id')->nullable();
            $table->unsignedInteger('original_taxon_name_id')->nullable();
            $table->string('name');
            $table->string('formatted_authors')->comment('命名者');
            $table->json('type_specimens')->comment('模型標本');
            $table->char('publish_year', 4)->nullable();
            $table->json('properties');
            $table->text('note');
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('nomenclature_id')->references('id')->on('nomenclatures');
            $table->foreign('rank_id')->references('id')->on('ranks');
            $table->foreign('original_taxon_name_id')->references('id')->on('taxon_names');
        });

        Schema::create('person_taxon_name', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('person_id');
            $table->unsignedInteger('taxon_name_id');
            $table->unsignedTinyInteger('role')->default(0);
            $table->timestamps();

            $table->foreign('person_id')->references('id')->on('persons');
            $table->foreign('taxon_name_id')->references('id')->on('taxon_names');
        });

        Schema::create('taxon_name_hybrid_parent', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('taxon_name_id');
            $table->unsignedInteger('parent_taxon_name_id');
            $table->timestamps();

            $table->foreign('taxon_name_id')->references('id')->on('taxon_names');
            $table->foreign('parent_taxon_name_id')->references('id')->on('taxon_names');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('taxon_names');
        Schema::dropIfExists('person_taxon_name');
        Schema::dropIfExists('taxon_name_hybrid_parent');
    }
}
