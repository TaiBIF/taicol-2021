<?php


namespace App\Exceptions;


class PersonDuplicateException extends \Exception
{
    protected $code = 101;
}