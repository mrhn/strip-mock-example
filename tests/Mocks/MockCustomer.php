<?php


namespace Tests\Mocks;


use Stripe\Customer;

class MockCustomer extends Customer
{
    public function __set($k, $v)
    {
        $this->{$k} = $v;
    }
}
