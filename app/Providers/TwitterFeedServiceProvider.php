<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\TweetAuthor;
use Atymic\Twitter\Twitter as TwitterContract;
use Illuminate\Http\JsonResponse;
use Twitter;
use Log;
use DateTime;
use App\Models\Tweets;


class TwitterFeedServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }

    public function followAuthor($screen_name)
    {
        $user_response = json_decode(Twitter::getUserByUsername($screen_name, []));

        if (!$user_response || !isset($user_response->data) || !isset($user_response->data->id))
        {
            Log::info("Unable to find author with screen name: ".$screen_name);
            return FALSE;
        }

        Log::info("Found new author ".$screen_name." with ID: ".$user_response->data->id);
        $new_author = TweetAuthor::create([
            'author_id' => $user_response->data->id,
            'name' => $user_response->data->name,
            'screen_name' => $user_response->data->screen_name,
        ]);

        if (!$new_author)
        {
            Log::error("Failed to add new author ".$screen_name);
            return FALSE;
        }
        Log::info("Added new author to DB.");
        return TRUE;
    }

    public function getAuthors()
    {
        $params = [
            'place.fields' => 'country,name',
            'tweet.fields' => 'author_id,geo',
            TwitterContract::KEY_RESPONSE_FORMAT => TwitterContract::RESPONSE_FORMAT_JSON,
        ];

        Log::info("Fetching list of twitter authors we are following.");
        $authors = TweetAuthor::all();

        return $authors;
    }

    //Get any related tweets without an NLP rating
    public function getUnratedTweets($key)
    {
        return Tweets::whereNull('nlp_sentiment')->relatedTweets($key)->get();
    }

    public function getRelatedTweets($key)
    {
        return Tweets::relatedTweets($key)->get();
    }

    /*
    * This method fetches all recent user tweets using the Twitter API, for a given twitter author_id.
    * 
    * TODO: Look at using search terms within twitter API - BUT I think it'll be more flexible to sync and then search our own data.
    * Some example searches:
    * (btc OR bitcoin OR crypto) AND (from:elonmusk)
    * 
    * ((btc OR bitcoin OR crypto) (#btc OR #bitcoin OR #buy OR #sell)) AND from:elonmusk
    * 
    * ((btc OR bitcoin OR crypto OR buy OR sell) OR (#btc OR #bitcoin OR #buy OR #sell)) AND from:elonmusk
    */
    public function getUserTweets($author_id)
    {
        Log::info("Fetching tweets for user ID: ".$author_id);
        $results = [];
        $response = "";
        $next = "";
        $tweets_added = 0;

        do {
            $params = [
                'max_results' => 100,
                'tweet.fields' => 'created_at,text,referenced_tweets'
            ];

            if ($next)
                $params['pagination_token'] = $next;

            //Set end_time to the time of the earliest tweet we have now.
            //This is because the tweet IDs do not seem to be necessarily sequential
            //but the created dates seem to be consistent.
            //
            //This allows us to filter out tweets we've already captured to get up to the max 3200 tweets.

            $earliest_tweet = Tweets::where('author_id', $author_id)->orderBy('tweet_created')->first();
            Log::info('Looked up earliest tweet:');
            Log::info(var_export($earliest_tweet, TRUE));

            if ($earliest_tweet) //If we have at least one existing tweet for this author.
            {
                $dt = new DateTime($earliest_tweet->tweet_created);
                Log::info("Setting end_time to: ".$dt->format('Y-m-d\TH:i:s\Z'));
                //Use ISO 8601 format
                $params['end_time'] = $dt->format('Y-m-d\TH:i:s\Z');
            }
            Log::info('Params used:');
            Log::info(print_r($params, TRUE));
            Log::info('*** Sending API Request for tweets...');

            $response = json_decode(Twitter::userTweets($author_id, $params));
            Log::info("Response: ");
            Log::info(var_export($response, TRUE));
            //ddd($response);

            if (!$response || !isset($response->data))
            {
                Log::info("Got invalid response");
                break;
            }

            foreach ($response->data as $tweet)
            {
                Log::info("Found new tweet:");
                Log::info(var_export($tweet, TRUE));
                //$results[] = $tweet;
                $tweet = Tweets::create([
                    'tweet_id' => $tweet->id,
                    'author_id' => $author_id,
                    'tweet_created' => $tweet->created_at,
                    'text' => $tweet->text,
                    'referenced_tweets' => (isset($tweet->referenced_tweets) ? json_encode($tweet->referenced_tweets) : ''),
                ]);

                if ($tweet)
                    $tweets_added++;
            }

            if (isset($response->meta) && isset($response->meta->next_token))
                $next = $response->meta->next_token;
            else
                $next = "";

            Log::info("Next token: ".$next);

            //$response = Twitter::searchRecent($query, $params);
        } while ($next);

        return $tweets_added;

    }
}
