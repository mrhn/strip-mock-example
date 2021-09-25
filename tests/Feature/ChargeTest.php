<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\StripeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Mockery\MockInterface;
use Stripe\Service\ChargeService;
use Stripe\Service\CustomerService;
use Stripe\StripeClient;
use Tests\Mocks\MockCharge;
use Tests\Mocks\MockCustomer;
use Tests\TestCase;

class ChargeTest extends TestCase
{
    use RefreshDatabase;

    public function testChargeStoreNoStripeCustomer(): void
    {
        $user = User::factory()->create();

        $stripeUserId = Str::random(64);
        $stripeChargeId = Str::random(64);
        $stripeAmount = 200.5;
        $stripeToken = 'tok_visa';

        $this->mockStripeWithCustomer($user, $stripeUserId, $stripeChargeId, $stripeAmount, $stripeToken);

        $response = $this->actingAs($user)
            ->json(
                'POST',
                route('charges.store'),
                [
                    'amount' => $stripeAmount,
                    'token' => $stripeToken,
                ]
            );

        $response->assertStatus(Response::HTTP_CREATED)
            ->assertJson([
                'amount' => $stripeAmount,
                'currency' => StripeService::CURRENCY,
                'type' => 'card',
                'user_id' => $user->id,
            ]);

        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseHas('users', [
            'stripe_id' => $stripeUserId,
        ]);

        $this->assertDatabaseCount('charges', 1);
        $this->assertDatabaseHas('charges', [
            'amount' => intval($stripeAmount * 100),
            'currency' => StripeService::CURRENCY,
            'type' => 'card',
            'user_id' => $user->id,
            'stripe_id' => $stripeChargeId
        ]);
    }

    public function testChargeStoreWithStripeCustomer(): void
    {
        $stripeUserId = Str::random(64);
        $user = User::factory()->create(['stripe_id' => $stripeUserId]);

        $stripeChargeId = Str::random(64);
        $stripeAmount = 200.5;
        $stripeToken = 'tok_visa';

        $this->mockStripeWithoutCustomer($stripeChargeId, $stripeAmount, $stripeToken);

        $response = $this->actingAs($user)
            ->json(
                'POST',
                route('charges.store'),
                [
                    'amount' => $stripeAmount,
                    'token' => $stripeToken,
                ]
            );

        $response->assertStatus(Response::HTTP_CREATED)
            ->assertJson([
                'amount' => $stripeAmount,
                'currency' => StripeService::CURRENCY,
                'type' => 'card',
                'user_id' => $user->id,
            ]);

        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseHas('users', [
            'stripe_id' => $stripeUserId,
        ]);

        $this->assertDatabaseCount('charges', 1);
        $this->assertDatabaseHas('charges', [
            'amount' => intval($stripeAmount * 100),
            'currency' => StripeService::CURRENCY,
            'type' => 'card',
            'user_id' => $user->id,
            'stripe_id' => $stripeChargeId
        ]);
    }

    public function testChargeValidationErrors(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->json(
                'POST',
                route('charges.store'),
                []
            );

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJson(
                [
                    'message' => 'The given data was invalid.',
                    'errors' => [
                        'amount' => [
                            'The amount field is required.'
                        ],
                        'token' => [
                            'The token field is required.'
                        ],
                    ],
                ]
            );
    }

    public function testChargeZeroError(): void
    {
        $user = User::factory()->create();

        $this->mock(StripeClient::class, function (MockInterface $mock) {});

        $response = $this->actingAs($user)
            ->json(
                'POST',
                route('charges.store'),
                [
                    'amount' => 0,
                    'token' =>  'tok_visa',
                ]
            );

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJson(
                [
                    'message' => 'Charges can not be zero.',
                ]
            );
    }

    private function mockStripeWithCustomer(User $user, string $stripeUserId, string $chargeId, float $amount, string $token): void
    {
        $this->mock(StripeClient::class, function (MockInterface $mock) use ($user, $stripeUserId, $chargeId, $amount, $token) {
            $customerServiceMock = \Mockery::mock(CustomerService::class);

            // Stripe objects can't set ids, mocking that too
            $customerObject = \Mockery::mock(MockCustomer::class);
            $customerObject->id = $stripeUserId;

            $customerServiceMock->shouldReceive('create')
                ->with([
                    'email' => $user->email,
                ])->once()
                ->andReturn($customerObject);

            $mock->customers = $customerServiceMock;

            $this->mockChargeStripe($mock, $chargeId, $amount, $token);
        });
    }

    private function mockStripeWithoutCustomer(string $chargeId, float $amount, string $token): void
    {
        $this->mock(StripeClient::class, function (MockInterface $mock) use ($chargeId, $amount, $token) {
            $customerServiceMock = \Mockery::mock(CustomerService::class);
            $customerServiceMock->shouldReceive('create')->never();

            $mock->customers = $customerServiceMock;

            $this->mockChargeStripe($mock, $chargeId, $amount, $token);
        });
    }

    private function mockChargeStripe(MockInterface $mock, string $chargeId, float $amount, string $token): void
    {
        $chargeServiceMock = \Mockery::mock(ChargeService::class);

        $stripeAmount = intval($amount * 100);

        $chargeObject = \Mockery::mock(MockCharge::class);
        $chargeObject->id = $chargeId;
        $chargeObject->currency = StripeService::CURRENCY;
        $chargeObject->amount = $stripeAmount;
        $chargeObject->payment_method_details = (object)['type' => 'card'];

        $chargeServiceMock->shouldReceive('create')
            ->with([
                'amount' => $stripeAmount,
                'currency' => StripeService::CURRENCY,
                'source' => $token,
                'description' => StripeService::CHARGE_DESCRIPTION,
            ])
            ->once()
            ->andReturn($chargeObject);

        $mock->charges = $chargeServiceMock;
    }
}
