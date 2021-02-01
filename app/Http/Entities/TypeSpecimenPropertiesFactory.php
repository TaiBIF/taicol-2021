<?php

namespace App\Http\Entities;

use App\TypeSpecimen;

class TypeSpecimenPropertiesFactory
{
    static public function prepareProperties($type) {
        switch($type) {
            case TypeSpecimen::TYPE_SPECIMEN:
                return new SpecimenPropertiesEntity();
            case TypeSpecimen::TYPE_IMAGE:
            case TypeSpecimen::TYPE_PHOTO:
            case TypeSpecimen::TYPE_DNA:
                return new OtherSpecimenPropertiesEntity();
        }
    }
}
