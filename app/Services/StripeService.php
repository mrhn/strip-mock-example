<?php


namespace App\Services;


use App\Exceptions\ChargeNotZeroException;
use App\Models\Charge;
use App\Models\User;
use Stripe\Charge as StripeCharge;
use Stripe\Customer;
use Stripe\StripeClient;

class StripeService
{
    public const CURRENCY = 'dkk';

    public const CHARGE_DESCRIPTION = 'Saas charge for stripe.';

    /** @var StripeClient */
    public $stripeClient;

    /** @var User */
    public $user;

    public function __construct(StripeClient $stripeClient)
    {
        $this->stripeClient = $stripeClient;
    }

    public function charge(float $amount, string $token): Charge
    {
        $amountInteger = intval($amount * 100);

        if ($amountInteger == 0) {
            throw new ChargeNotZeroException();
        }

        $this->findOrCreateCustomer();
        $stripeCharge = $this->createCharge($amountInteger, $token);

        $charge = $this->user->charges()->create(
            [
                'amount' => $stripeCharge->amount,
                'currency' => $stripeCharge->currency,
                'type' => $stripeCharge->payment_method_details->type,
                'stripe_id' => $stripeCharge->id,
                'customer' => $this->user->stripe_id,
            ]
        );

        return $charge;
    }

    public function createCharge(int $amount, string $token): StripeCharge
    {
        return $this->stripeClient->charges->create([
            'amount' => $amount,
            'currency' => static::CURRENCY,
            'source' => $token,
            'description' => static::CHARGE_DESCRIPTION,
        ]);
    }

    public function findOrCreateCustomer(): ?Customer
    {
        if ($this->user->stripe_id) {
            return null;
        }

        $customer = $this->stripeClient->customers->create(
            [
                'email' => $this->user->email,
            ]
        );

        $this->user->stripe_id = $customer->id;
        $this->user->save();

        return $customer;
    }

    public function setUser(User $user): self
    {
        $this->user = $user;

        return $this;
    }
}
