<?php


namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class Charge
 * @package App\Models
 *
 * @property float $amount
 * @property string $currency
 * @property string $type
 */
class Charge extends Model
{
    protected $fillable = [
        'amount',
        'currency',
        'type',
    ];

    public function getAmountAttribute(): float
    {
        return $this->attributes['amount'] / 100;
    }
}
