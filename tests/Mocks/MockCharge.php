<?php


namespace Tests\Mocks;


use Stripe\Charge;
use Stripe\Customer;

class MockCharge extends Charge
{
    public function __set($k, $v)
    {
        $this->{$k} = $v;
    }
}
