<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use DateTime;
use App\Models\MarketData;

class ArbitrageAlgorithmService extends ServiceProvider
{
    private $startDate;
    private $endDate;

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

    public function getData($start, $end, $currency)
    {
        $this->startDate = $start;
        $this->endDate = $end;

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

        $trackBinanceLastValue = $rowsBinance[0]->open_price;
        $trackBinanceWasLower = ($rowsBinance[0]->open_price < $rowsFTX[0]->open_price);

        //For each point, compare FTX to Binance
        //if the last value of Binance was lower than FTX and is 
        //now higher than FTX, then it is bearish.
        //Otherwise bullish
        for ($i = 0; $i < $count; $i++)
        {
            if ($trackBinanceWasLower && ($rowsBinance[$i]->open_price < $rowsFTX[$i]->open_price))
            {
                //Crossed into a bullish market. So buy? Add a buy point
                $newPts[] = ['type' => 'buy', 'date' => $rowsFTX[$i]->date->format('d M H:i'), 'open_price' => $rowsFTX[$i]->open_price];
            }
            else if (!$trackBinanceWasLower && ($rowsBinance[$i]->open_price > $rowsFTX[$i]->open_price))
            {
                //Crossed into a bearish market. So sell? Add a sell point
                $newPts[] = ['type' => 'sell', 'date' => $rowsFTX[$i]->date->format('d M H:i'), 'open_price' => $rowsFTX[$i]->open_price];
            }
            else
            {
                //Add pt as an empty point for the chart to skip over.
                $newPts[] = ['type' => 'empty', $rowsFTX[$i]->date->format('d M H:i'), 'open_price' => $rowsFTX[$i]->open_price];
            }

            $trackBinanceWasLower = ($rowsBinance[$i]->open_price < $rowsFTX[$i]->open_price);
            $trackBinanceLastValue = $rowsBinance[$i]->open_price;
        }
        return $newPts;       
    }
}
