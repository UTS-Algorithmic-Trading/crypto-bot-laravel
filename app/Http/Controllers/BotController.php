<?php

namespace App\Http\Controllers;

use App\Models\Bot;
use Illuminate\Http\Request;
use Auth;
use Validator;

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
}
