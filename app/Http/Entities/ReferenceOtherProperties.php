<?php


namespace App\Http\Entities;


interface ReferenceOtherProperties
{
    public function setProperties(Array $data);

    public function toJsonString();

    public function toArray();
}
