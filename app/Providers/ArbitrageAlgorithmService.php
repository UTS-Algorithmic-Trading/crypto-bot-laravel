<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use DateTime;
use App\Models\MarketData;
use Log;

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
        $trackBianceLastDelta = ($rowsBinance[0]->close_price - $rowsFTX[0]->close_price);
        $trackBinanceWasLower = ($rowsBinance[0]->close_price < $rowsFTX[0]->close_price);
        Log::info("Binance - Last Value: ".$trackBinanceLastValue);
        Log::info("Binance - Last Delta: ".$trackBianceLastDelta);
        Log::info("Binance - Binance Was Lower: ".$trackBinanceWasLower);


        //For each point, compare FTX to Binance
        //if the last value of Binance was lower than FTX and is 
        //now higher than FTX, then it is bearish.
        //Otherwise bullish
        for ($i = 0; $i < $count; $i++)
        {
            $binanceCurrentDelta = abs($rowsBinance[$i]->close_price - $rowsFTX[$i]->close_price);

            if ($trackBinanceWasLower && ($rowsBinance[$i]->close_price < $rowsFTX[$i]->close_price))
            {
               
                if (($rowsBinance[$i]->close_price < $trackBinanceLastValue) && ($binanceCurrentDelta > $trackBianceLastDelta))
                {
                     //If Binance was lower and is now lower than last Binance value AND delta is higher then keep waiting till it just starts to rise.
                     $newPts[] = ['type' => 'empty', $rowsFTX[$i]->date->format('d M H:i'), 'close_price' => $rowsFTX[$i]->close_price];
                }
                else
                {
                    //If Binance is higher than last value then it's starting to rise (hopefully) but is still lower than FTX, so now is an optimal time to buy.
                    $newPts[] = ['type' => 'buy', 'date' => $rowsFTX[$i]->date->format('d M H:i'), 'close_price' => $rowsFTX[$i]->close_price];
                }
            }
            //If Binance was higher than FTX and is still higher than FTX:
            else if (!$trackBinanceWasLower && ($rowsBinance[$i]->close_price > $rowsFTX[$i]->close_price))
            {
                //If Binance is higher than last value AND delta is higher than last delta, keep waiting for it to peak
                if (($rowsBinance[$i]->close_price > $trackBinanceLastValue) && ($binanceCurrentDelta > $trackBianceLastDelta))
                {
                    //Keep waiting for peak
                    $newPts[] = ['type' => 'empty', $rowsFTX[$i]->date->format('d M H:i'), 'close_price' => $rowsFTX[$i]->close_price];
                }
                else
                {
                    $newPts[] = ['type' => 'sell', 'date' => $rowsFTX[$i]->date->format('d M H:i'), 'close_price' => $rowsFTX[$i]->close_price];
                }
            }
            else
            {
                //Add pt as an empty point for the chart to skip over.
                $newPts[] = ['type' => 'empty', $rowsFTX[$i]->date->format('d M H:i'), 'close_price' => $rowsFTX[$i]->close_price];
            }

            $trackBinanceWasLower = ($rowsBinance[$i]->close_price < $rowsFTX[$i]->close_price);
            $trackBinanceLastValue = $rowsBinance[$i]->close_price;
            $trackBianceLastDelta = abs($rowsBinance[$i]->close_price - $rowsFTX[$i]->close_price);
            Log::info("Binance - Last Value: ".$trackBinanceLastValue);
            Log::info("Binance - Last Delta: ".$trackBianceLastDelta);
            Log::info("Binance - Binance Was Lower: ".$trackBinanceWasLower);

        }
        return $newPts;       
    }
}
