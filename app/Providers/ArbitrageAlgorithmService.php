<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use DateTime;
use Log;
use App\Models\Bot;
use App\Models\MarketData;
use App\Models\Simulation;
use App\Models\SimulationEntry;

class ArbitrageAlgorithmService extends ServiceProvider
{
    private $startDate;
    private $endDate;
    private $bot;
    private $amount_held; //This is how much currency we have bought during the simulation.
    private $profit;

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

    private function performTransaction($simulation, $amount, $buy_exchange, $sell_exchange, $currency, $buy_price, $sell_price)
    {
        Log::info('Running Simulation ID: '.$simulation->id);
        $sell_amount = $amount / $sell_price;
        $buy_amount = $amount / $buy_price;
        $profit_amount = $buy_amount - $sell_amount;
        SimulationEntry::create([
            'simulation_id' => $simulation->id,
            'bot_id' => $this->bot->id,
            'amount' => $amount,
            'buy_price' => $buy_price,
            'sell_price' => $sell_price,
            'description' => 'Bought $'.$buy_amount.' of '.$currency.' from '.$buy_exchange.' and sold $'.$sell_amount.' at '.$sell_exchange.' for profit of: '.$profit_amount,
        ]);

        $this->profit += $profit_amount;
    }

    private function performBuy($simulation, $amount, $exchange, $price, $currency)
    {
        //If we are buying $500 (amount) of BTC then divide amount by the current BTC exchange rate (price)
        $buy_amount = $amount / $price;
        SimulationEntry::create([
            'simulation_id' => $simulation->id,
            'bot_id' => $this->bot->id,
            'amount' => $amount,
            'buy_price' => $price,
            'sell_price' => 0,
            'description' => 'Bought $'.$buy_amount.' of '.$currency.' from '.$exchange,
        ]);
    }

    private function performSell($simulation, $amount, $exchange, $price, $currency)
    {
        //If we are selling $500 (amount) of BTC then divide amount by the current BTC exchange rate (price)
        $sell_amount = $amount / $price;
        SimulationEntry::create([
            'simulation_id' => $simulation->id,
            'bot_id' => $this->bot->id,
            'amount' => $amount,
            'buy_price' => 0,
            'sell_price' => $price,
            'description' => 'Sold $'.$sell_amount.' of '.$currency.' from '.$exchange,
        ]);
    }

    public function getData($start, $end, $currency)
    {
        $this->startDate = $start;
        $this->endDate = $end;
        $this->profit = 0;

        $this->bot = Bot::findOrFail(1); //Hardcode bot 1 for now.

        $simulation = Simulation::create([
            'user_id' => auth()->user()->id,
            'currency' => $currency,
            'algorithm_name' => 'Arbitrage',
            'start_time' => new DateTime(),
        ]);

        $rowsBinance = MarketData::where('symbol', $currency)
                            ->where('date', '>=', $this->startDate)
                            ->where('date', '<=', $this->endDate)
                            ->where('exchange', 1)->get(); //Exchange 1 is binance
        
        $rowsFTX = MarketData::where('symbol', $currency)
                            ->where('date', '>=', $this->startDate)
                            ->where('date', '<=', $this->endDate)
                            ->where('exchange', 2)->get(); //Exchange 2 is FTX

        $count = min(count($rowsBinance), count($rowsFTX));

        if ($count == 0)
            return [];

        $newPts = [];

        $trackBinanceLastValue = $rowsBinance[0]->close_price;
        $trackBinanceWasLower = ($rowsBinance[0]->close_price < $rowsFTX[0]->close_price);
        $lastBuyValue = 0;

        //For each point, compare FTX to Binance
        //if the last value of Binance was lower than FTX and is 
        //now higher than FTX, then it is bearish.
        //Otherwise bullish
        for ($i = 0; $i < $count; $i++)
        {
            if ($trackBinanceWasLower && ($rowsBinance[$i]->close_price > $rowsFTX[$i]->close_price))
            {
                $this->performTransaction($simulation, $this->bot->max_bet, 'FTX', 'Binance', 'BTC/USDT', $rowsFTX[$i]->close_price, $rowsBinance[$i]->close_price);
                $newPts[] = ['type' => 'both', 'date' => $rowsFTX[$i]->date->format('d M H:i'), 'buy_price' => $rowsFTX[$i]->close_price, 'sell_price' => $rowsBinance[$i]->close_price];
                $lastBuyValue = $rowsBinance[$i]->close_price;
            }
            //If Binance was higher than FTX and is now lower than FTX:
            else if (!$trackBinanceWasLower && ($rowsBinance[$i]->close_price < $rowsFTX[$i]->close_price))
            {
                $this->performTransaction($simulation, $this->bot->max_bet, 'Binance', 'FTX', 'BTC/USDT', $rowsBinance[$i]->close_price, $rowsFTX[$i]->close_price);
                $newPts[] = ['type' => 'both', 'date' => $rowsFTX[$i]->date->format('d M H:i'), 'buy_price' => $rowsBinance[$i]->close_price, 'sell_price' => $rowsFTX[$i]->close_price];
            }
            else
            {
                //Add pt as an empty point for the chart to skip over.
                $newPts[] = ['type' => 'empty', $rowsFTX[$i]->date->format('d M H:i'), 'close_price' => $rowsBinance[$i]->close_price];
            }

            $trackBinanceWasLower = ($rowsBinance[$i]->close_price < $rowsFTX[$i]->close_price);
            $trackBinanceLastValue = $rowsBinance[$i]->close_price;
        }

        $simulation->end_time = new DateTime();
        $simulation->total_profit = $this->profit;
        $simulation->save();

        //Get the latest currency conversion according to the market data to find out how much profit you would make in USD.
        $currency_conversion = MarketData::where('symbol', $simulation->currency)->where('exchange', 1)->orderBy('date', 'desc')->first();
        $rate_usdt = $currency_conversion->close_price;
        $profit_usdt = $simulation->total_profit * $rate_usdt;

        return ['simulation' => $simulation, 'data' => $newPts, 'profit_usdt' => $profit_usdt, 'rate_usdt' => $rate_usdt];       
    }

    /**
     * This will perform a buy and sell for EVERY point where the price of FTX vs Binance is not the same.
     */
    public function getDataV2($start, $end, $currency)
    {
        $this->startDate = $start;
        $this->endDate = $end;
        $this->profit = 0;
        $this->amount_held = 0;

        $this->bot = Bot::findOrFail(1); //Hardcode bot 1 for now.

        $simulation = Simulation::create([
            'user_id' => auth()->user()->id,
            'currency' => $currency,
            'algorithm_name' => 'Arbitrage V2',
            'start_time' => new DateTime(),
        ]);

        $rowsBinance = MarketData::where('symbol', $currency)
                            ->where('date', '>=', $this->startDate)
                            ->where('date', '<=', $this->endDate)
                            ->where('exchange', 1)->get(); //Exchange 1 is binance
        
        $rowsFTX = MarketData::where('symbol', $currency)
                            ->where('date', '>=', $this->startDate)
                            ->where('date', '<=', $this->endDate)
                            ->where('exchange', 2)->get(); //Exchange 2 is FTX

        $count = min(count($rowsBinance), count($rowsFTX));

        if ($count == 0)
            return [];

        $newPts = [];

        //For each point, compare FTX to Binance
        //If one market is lower, buy in that market, sell in the other.
        for ($i = 0; $i < $count; $i++)
        {
            //Buy binance if lower
            if ($rowsBinance[$i]->close_price < $rowsFTX[$i]->close_price)
            {
                $this->performTransaction($simulation, $this->bot->max_bet, 'Binance', 'FTX', 'BTC/USDT', $rowsBinance[$i]->close_price, $rowsFTX[$i]->close_price);
                $newPts[] = ['type' => 'both', 'date' => $rowsFTX[$i]->date->format('d M H:i'), 'buy_price' => $rowsBinance[$i]->close_price, 'sell_price' => $rowsFTX[$i]->close_price];
                $lastBuyValue = $rowsBinance[$i]->close_price;
            }
            else if ($rowsBinance[$i]->close_price > $rowsFTX[$i]->close_price)
            {
                $this->performTransaction($simulation, $this->bot->max_bet, 'FTX', 'Binance', 'BTC/USDT', $rowsFTX[$i]->close_price, $rowsBinance[$i]->close_price);
                $newPts[] = ['type' => 'both', 'date' => $rowsFTX[$i]->date->format('d M H:i'), 'buy_price' => $rowsFTX[$i]->close_price, 'sell_price' => $rowsBinance[$i]->close_price];
            }
            else
            {
                //Add pt as an empty point for the chart to skip over.
                $newPts[] = ['type' => 'empty', $rowsFTX[$i]->date->format('d M H:i'), 'close_price' => $rowsBinance[$i]->close_price];
            }
        }

        $simulation->end_time = new DateTime();
        $simulation->total_profit = $this->profit;
        $simulation->save();

        //Get the latest currency conversion according to the market data to find out how much profit you would make in USD.
        $currency_conversion = MarketData::where('symbol', $simulation->currency)->where('exchange', 1)->orderBy('date', 'desc')->first();
        $rate_usdt = $currency_conversion->close_price;
        $profit_usdt = $simulation->total_profit * $rate_usdt;

        return ['simulation' => $simulation, 'data' => $newPts, 'profit_usdt' => $profit_usdt, 'rate_usdt' => $rate_usdt];       
    }
}
