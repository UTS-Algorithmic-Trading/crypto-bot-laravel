<?php

namespace App\Http\Controllers;

use App\Models\Simulation;
use App\Models\MarketData;
use Illuminate\Http\Request;

class SimulationController extends Controller
{
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
     * @param  \App\Models\Simulation  $simulation
     * @return \Illuminate\Http\Response
     */
    public function show(Simulation $simulation)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Simulation  $simulation
     * @return \Illuminate\Http\Response
     */
    public function edit(Simulation $simulation)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Simulation  $simulation
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Simulation $simulation)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Simulation  $simulation
     * @return \Illuminate\Http\Response
     */
    public function destroy(Simulation $simulation)
    {
        //
    }

    public function get_profit(Simulation $simulation)
    {
        //Get the latest currency conversion according to the market data to find out how much profit you would make in USD.
        $currency_conversion = MarketData::where('symbol', $simulation->currency)->where('exchange', 1)->orderBy('date', 'desc')->first();
        $rate_usdt = $currency_conversion->close_price;

        //Profit in USDT
        $profit_usdt = $simulation->total_profit * $rate_usdt;
        return ['profit_usdt' => $profit_usdt, 'rate_usdt' => $rate_usdt];
    }
}
