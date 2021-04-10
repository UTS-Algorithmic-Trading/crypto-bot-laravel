<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasTimestamps;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Validator;


class Bot extends Model
{
    use HasFactory;
    use SoftDeletes;


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
        'trade_started_at',
        'trade_ended_at'
    ];

    /**
     * Fillable fields for a Profile.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'user_id',
        'trade_started_at',
        'trade_ended_at',
        'initial_bet',
        'max_bet',
        'stock_type',
        'current_high_price',
        'current_low_price',
        'target_profit_percent',
        'trailing_profit_percent',
        'stop_loss_percent',
    ];

    /**
     * Get a validator for an incoming registration request.
     *
     * @param array $data
     *
     * @return array
     */
    public static function rules($id = 0, $merge = [])
    {
        return array_merge(
            [
                'name'   => 'required|min:3|max:50|unique:bots,name,'.($id ? $id : 'NULL').',id,deleted_at,NULL',
                'initial_bet'  => 'required|numeric|between:1,10000',
                'max_bet'  => 'required|numeric|between:100,100000',
                'stock_type' => 'required|alpha',
                'target_profit_percent' => 'required|numeric|between:0,999',
                'trailing_profit_percent' => 'required|numeric|between:0,100',
                'stop_loss_percent' => 'required|numeric|between:0,100',
            ],
            $merge
        );
    }

}
