<?php


namespace App\Exceptions;


use Throwable;

class ChargeNotZeroException extends \Exception
{
    public function __construct($message = "Charges can not be zero.", $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
