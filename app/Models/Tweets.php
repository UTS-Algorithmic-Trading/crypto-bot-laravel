<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tweets extends Model
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
        'tweet_created',
    ];

    /**
     * Fillable fields for a Profile.
     *
     * @var array
     */
    protected $fillable = [
        'tweet_id',
        'author_id',
        'tweet_created',
        'text',
        'referenced_tweets',
        'nlp_sentiment',
    ];


    /**
     * Use this local scope to filter tweets down to
     * only those which match the keywords for a given currency.
     * https://laravel.com/docs/8.x/eloquent#local-scopes
     */
    public function scopeRelatedTweets($query, $key)
    {
        $keywords = [
            'BTC' => [
                'bitcoin', 'btc', 'crypto', 'currenc',
            ],

            'ETH' => [
                'ethereum', 'eth', 'crypto', 'currenc',
            ],
        ];

        //https://stackoverflow.com/questions/29548073/laravel-advanced-wheres-how-to-pass-variable-into-function
        //Use a closure to pass variables into the anonymous function to group WHERE clauses
        //WHERE X AND ... (text LIKE %foo% OR text LIKE %bar% OR text LIKE %baz%)
        return $query->where(function ($query) use ($keywords, $key) {
            foreach ($keywords[$key] as $term)
            {
                $query->orWhere('text', 'LIKE', '%'.$term.'%');
            }
        });
    }
}
