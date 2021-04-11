<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;

class MarketData extends Model
{
    use HasFactory;

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = true;

    /**
     * The attributes that are not mass assignable.
     *
     * @var array
     */
    protected $guarded = [
        'id',
    ];

    /**
     * The attributes that are hidden.
     *
     * @var array
     */
    protected $hidden = [];

        /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at',
        'date',
    ];

    /**
     * Fillable fields for a Profile.
     *
     * @var array
     */
    protected $fillable = [
        'date',
        'symbol',
        'open_price',
        'high_price',
        'low_price',
        'close_price',
        'crypto_currency_volume',
        'base_currency_volume',
        'trade_count',
    ];

    /**
     * Get a validator for a market data row
     *
     * @param array $data
     *
     * @return array
     */
    public static function rules($id = 0, $merge = [])
    {
        return array_merge(
            [
                //Examples of doing a unique validation on two columns:
                //- https://github.com/Maatwebsite/Laravel-Excel/issues/2709
                //- https://github.com/Maatwebsite/Laravel-Excel/issues/2872
                'date' => [
                    'required', 
                    /*
                    'unique:market_data,NULL,'
                    Rule::unique('market_data', '*.date', '*.symbol'), */
                ],
                'symbol'   => [
                    'required', 'min:3', 'max:20',
                    'in:BTC/USDT,ETH/USDT',
                ],
                'open_price'  => 'required|numeric|between:0,1000000',
                'high_price'  => 'required|numeric|between:0,1000000',
                'low_price'  => 'required|numeric|between:0,1000000',
                'close_price'  => 'required|numeric|between:0,1000000',
                'crypto_currency_volume'  => 'required|numeric|between:0,1000000',
                'base_currency_volume'  => 'required|numeric|between:0,1000000',
                'trade_count'  => 'required|numeric|between:0,1000',
            ],
            $merge
        );
    }
}
