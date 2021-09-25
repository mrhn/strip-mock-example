<?php


namespace App\Exceptions;


use Illuminate\Http\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

class ChargeNotZeroException extends HttpException
{
    public function __construct()
    {
        parent::__construct(Response::HTTP_UNPROCESSABLE_ENTITY, 'Charges can not be zero.');
    }
}
