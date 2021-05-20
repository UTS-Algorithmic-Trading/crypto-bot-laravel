<?php

namespace App\Http\Controllers;

use App\Models\Bot;
use Illuminate\Http\Request;
use Auth;
use Validator;
use Atymic\Twitter\Twitter as TwitterContract;
use Illuminate\Http\JsonResponse;
use Twitter;
use Log;
use DateTime;
use App\Models\Tweets;

class BotController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }


    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $bots = Bot::where('user_id', auth()->user()->id)->get();
        return view('bots.index', ['bots' => $bots]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('bots.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), Bot::rules());

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $bot = Bot::create(array_merge($validator->valid(), [
            'user_id' => auth()->user()->id,
            'trade_started_at' => date('Y-m-d H:i:s'),
        ]));

        return redirect('bots/'.$bot->id)->with('success', 'Successfully created new bot');
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Bot  $bot
     * @return \Illuminate\Http\Response
     */
    public function show(Bot $bot)
    {
        return view('bots.view', ['bot' => $bot]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Bot  $bot
     * @return \Illuminate\Http\Response
     */
    public function edit(Bot $bot)
    {
        return view('bots.edit', ['bot' => $bot]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Bot  $bot
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Bot $bot)
    {
        $validator = Validator::make($request->all(), Bot::rules($bot->id));

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $bot->update($validator->valid());

        return redirect('bots/'.$bot->id)->with('success', 'Successfully updated bot');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Bot  $bot
     * @return \Illuminate\Http\Response
     */
    public function destroy(Bot $bot)
    {
        try
        {
            $bot->delete();
        }
        catch (\Exception $e)
        {
            return back()->withErrors($e->getMessage())->withInput();
        }

        request()->session()->flash('success', 'Bot Deleted');

        return response()->json([
            'status' => 'OK',
            'message' => 'Bot Deleted',
        ]);

    }

    public function searchRecent(string $query)
    {
        
        $params = [
            'place.fields' => 'country,name',
            'tweet.fields' => 'author_id,geo',
            'expansions' => 'author_id,in_reply_to_user_id',
            TwitterContract::KEY_RESPONSE_FORMAT => TwitterContract::RESPONSE_FORMAT_JSON,
        ];

        $user_response = json_decode(Twitter::getUserByUsername("elonmusk", []));
        Log::info("Fetching tweets for user ID: ".$user_response->data->id);

        $results = [];
        $response = "";
        $next = "";

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

            $earliest_tweet = Tweets::where('author_id', $user_response->data->id)->orderBy('tweet_created')->first();
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

            $response = json_decode(Twitter::userTweets($user_response->data->id, $params));
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
                Tweets::create([
                    'tweet_id' => $tweet->id,
                    'author_id' => $user_response->data->id,
                    'tweet_created' => $tweet->created_at,
                    'text' => $tweet->text,
                    'referenced_tweets' => (isset($tweet->referenced_tweets) ? json_encode($tweet->referenced_tweets) : ''),
                ]);
            }

            if (isset($response->meta) && isset($response->meta->next_token))
                $next = $response->meta->next_token;
            else
                $next = "";

            Log::info("Next token: ".$next);

            //$response = Twitter::searchRecent($query, $params);
        } while ($next);

        ddd($results);

        return JsonResponse::fromJsonString($response);

        /*
         * Some example searches:
         * (btc OR bitcoin OR crypto) AND (from:elonmusk)
         * 
         * ((btc OR bitcoin OR crypto) (#btc OR #bitcoin OR #buy OR #sell)) AND from:elonmusk
         * 
         * ((btc OR bitcoin OR crypto OR buy OR sell) OR (#btc OR #bitcoin OR #buy OR #sell)) AND from:elonmusk
         */
    }
}
