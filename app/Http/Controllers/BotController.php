<?php

namespace App\Http\Controllers;

use App\Models\Bot;
use Illuminate\Http\Request;
use Auth;
use Validator;
use Atymic\Twitter\Twitter as TwitterContract;
use Illuminate\Http\JsonResponse;
use Twitter;
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
        //ddd($user_response);

        $response = Twitter::userTweets($user_response->data->id, []);
        //$response = Twitter::searchRecent($query, $params);
        ddd(json_decode($response));
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
