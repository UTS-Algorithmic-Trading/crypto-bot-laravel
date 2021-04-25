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
                $newPts[] = ['type' => 'buy', 'date' => $rowsFTX[$i]->date->format('d M H:i'), 'close_price' => $rowsBinance[$i]->close_price];
                $lastBuyValue = $rowsBinance[$i]->close_price;
            }
            //If Binance was higher than FTX and is now lower than FTX:
            else if (!$trackBinanceWasLower && ($rowsBinance[$i]->close_price < $rowsFTX[$i]->close_price) && $rowsBinance[$i]->close_price > $lastBuyValue)
            {
                $newPts[] = ['type' => 'sell', 'date' => $rowsFTX[$i]->date->format('d M H:i'), 'close_price' => $rowsBinance[$i]->close_price];
            }
            else
            {
                //Add pt as an empty point for the chart to skip over.
                $newPts[] = ['type' => 'empty', $rowsFTX[$i]->date->format('d M H:i'), 'close_price' => $rowsBinance[$i]->close_price];
            }

            $trackBinanceWasLower = ($rowsBinance[$i]->close_price < $rowsFTX[$i]->close_price);
            $trackBinanceLastValue = $rowsBinance[$i]->close_price;
        }
        return $newPts;       
    }
}
