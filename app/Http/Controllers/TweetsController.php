<?php

namespace App\Http\Controllers;

use App\Models\Tweets;
use App\Providers\SocialSentimentServiceProvider;
use App\Providers\TwitterFeedServiceProvider;
use Illuminate\Http\Request;
use Log;

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

        $authors = $this->twitterService->getAuthors();

        foreach ($authors as $author)
        {
            //First update the list of tweets.
            Log::info('Syncing tweets for author: '.$author->screen_name);
            $new_tweet_count = $this->twitterService->getUserTweets($author->author_id);
            Log::info('Synced '.$new_tweet_count.' new tweets.');
        }

        //Now find any tweets that have not been rated.
        $tweets = $this->twitterService->getUnratedTweets($currency);

        $nlp_rated_count = 0;
        foreach ($tweets as $tweet)
        {
            $sentiment = $this->nlpService->getSentiment($tweet->text);
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
        $sentiment = $this->twitterService->getSentiment();
        $authors = $this->twitterService->getAuthors();

        return view('tweets.summary', [
            'sentiment' => $sentiment,
            'authors' => $authors,
        ]);
    }

    public function get_sentiment()
    {
        $sentiment = $this->twitterService->getSentiment();
        ddd($sentiment);
        return response()->json($sentiment);
    }
}
