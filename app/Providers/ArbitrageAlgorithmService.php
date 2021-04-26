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

    private function performTransaction($simulation, $buy_exchange, $sell_exchange, $currency, $buy_price, $sell_price)
    {
        Log::info('Running Simulation ID: '.$simulation->id);
        $amount = $this->bot->max_bet;
        $sell_amount = $sell_price * $amount;
        $buy_amount = $buy_price * $amount;
        $profit_amount = ($sell_price - $buy_price) * $amount;
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

    public function getData($start, $end, $currency)
    {
        $this->startDate = $start;
        $this->endDate = $end;

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
                $this->performTransaction($simulation, 'FTX', 'Binance', 'BTC/USDT', $rowsFTX[$i]->close_price, $rowsBinance[$i]->close_price);
                $newPts[] = ['type' => 'sell', 'date' => $rowsFTX[$i]->date->format('d M H:i'), 'close_price' => $rowsBinance[$i]->close_price];
                $lastBuyValue = $rowsBinance[$i]->close_price;
            }
            //If Binance was higher than FTX and is now lower than FTX:
            else if (!$trackBinanceWasLower && ($rowsBinance[$i]->close_price < $rowsFTX[$i]->close_price) && $rowsBinance[$i]->close_price > $lastBuyValue)
            {
                $this->performTransaction($simulation, 'Binance', 'FTX', 'BTC/USDT', $rowsBinance[$i]->close_price, $rowsFTX[$i]->close_price);
                $newPts[] = ['type' => 'buy', 'date' => $rowsFTX[$i]->date->format('d M H:i'), 'close_price' => $rowsBinance[$i]->close_price];
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

        return $newPts;       
    }
}
