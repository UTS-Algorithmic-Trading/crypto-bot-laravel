<?php

namespace App\Http\Controllers;

use App\Models\Tweets;
use App\Providers\SocialSentimentServiceProvider;
use App\Providers\TwitterFeedServiceProvider;
use Illuminate\Http\Request;

class TweetsController extends Controller
{
    private $twitterService;
    private $nlpService;

    public function __construct(TwitterFeedServiceProvider $twitter, SocialSentimentServiceProvider $nlp)
    {
        $this->twitterService = $twitter;
        $this->nlpService = $nlp;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Tweets  $tweets
     * @return \Illuminate\Http\Response
     */
    public function show(Tweets $tweets)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Tweets  $tweets
     * @return \Illuminate\Http\Response
     */
    public function edit(Tweets $tweets)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Tweets  $tweets
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Tweets $tweets)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Tweets  $tweets
     * @return \Illuminate\Http\Response
     */
    public function destroy(Tweets $tweets)
    {
        //
    }

    public function sync($symbol)
    {
        if (strpos($symbol, '/') !== FALSE)
        {
            $parts = explode('/', $symbol);
            $currency = $parts[0];
        }
        else
        {
            $currency = $symbol;
        }
        Log::info('Syncing tweets and doing NLP for currency: '.$currency);

        $authors = $this->twitter->getAuthors();

        foreach ($authors as $author)
        {
            //First update the list of tweets.
            Log::info('Syncing tweets for author: '.$author->screen_name);
            $new_tweet_count = $this->twitter->getUserTweets($author->author_id);
            Log::info('Synced '.$new_tweet_count.' new tweets.');
        }

        //Now find any tweets that have not been rated.
        $tweets = $this->twitter->getUnratedTweets($currency)->get();

        $nlp_rated_count = 0;
        foreach ($tweets as $tweet)
        {
            $sentiment = $this->nlp->getSentiment($tweet->text);
            Log::info('Found sentiment of '.$sentiment['score'].' for text '.$tweet->text);
            $tweet->nlp_sentiment = $sentiment['score'];
            $tweet->update();
            $nlp_rated_count++;
        }

        return response()->json([
            'new_tweet_count' => $new_tweet_count,
            'nlp_rated_count' => $nlp_rated_count, 
        ]);
    }

    public function summary()
    {


        return view('tweets.summary', [
            'tweets' => $tweets,
            ''
        ]);
    }

    public function sentiment()
    {
        //Find all tweets where NLP not null and keywords match in date range.
        //Find avg sentiment.

        $keys = ['BTC', 'ETH'];
        $sentiment = [];
        foreach ($keys as $k)
        {
            $tweets = $this->twitter->getRelatedTweets($k)->whereNotNull('nlp_sentiment')->get();
            $score = 0;
            $count = 0;
            foreach ($tweets as $t)
            {
                $count++;
                $score += $t->nlp_sentiment;
            }
            $sentiment[$k] = [
                'total' => $score,
                'average' => $score / $count,
                'count' => $count,
            ];
        }

        return response()->json($sentiment);
    }
}
