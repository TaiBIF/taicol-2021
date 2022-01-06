<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAllModelView extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // 要特別設定 person collate
        DB::statement("
            CREATE ALGORITHM=UNDEFINED SQL SECURITY DEFINER VIEW `all_models`
            AS SELECT
               `references`.`id` AS `id`,
               `references`.`title` AS `title`,
               'reference' AS `n`
            FROM `references`
                UNION
                    select `persons`.`id` AS `id`,
                            concat(`persons`.`last_name`,', ',`persons`.`first_name`,' ',`persons`.`middle_name`) AS `title`,
                            'person' COLLATE 'utf8mb4_unicode_ci' AS `n`
                    from `persons`
                UNION select `taxon_names`.`id` AS `id`,`taxon_names`.`name` AS `title`,'taxon_name' AS `n` from `taxon_names`;
        ");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
    }
}
