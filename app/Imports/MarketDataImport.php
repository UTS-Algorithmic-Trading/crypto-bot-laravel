<?php

namespace App\Imports;

use App\Models\MarketData;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\WithChunkReading;

class MarketDataImport implements ToModel, WithValidation, WithBatchInserts, WithChunkReading, SkipsOnError
{
    public $exchange = 1;
    private $rows = 0;

    public function model(array $row)
    {
        $this->rows++;

        $epoch = substr($row[0], 0, 10);
        //E.g. Epoch of 1599856800000 should give Friday, 11 September 2020 20:40:00
        //It Should be  1599856800
        //The Max epoch 2147483647 
        //But I get a time of 52667-06-12 02:40:00 and so it must be wrapping around somehow.
        //EDIT: Yep, it's 3 digits too long. Going to remove last 3 digits. Same issue happens for both DateTime and gmdate()

        return new MarketData([
            'exchange' => $this->exchange,
            'date' => new \DateTime('@'.$epoch),
            'symbol' => $row[2],
            'open_price' => $row[3],
            'high_price' => $row[4],
            'low_price' => $row[5],
            'close_price' => $row[6],
            'crypto_currency_volume' => $row[7],
            'base_currency_volume' => $row[8],
            'trade_count' => (isset($row[9]) ? $row[9] : 0),
        ]);
    }

    public function batchSize(): int
    {
        return 1000;
    }

    public function chunkSize(): int
    {
        return 1000;
    }

        /**
     * @param \Throwable $e
     */
    public function onError(\Throwable $e)
    {
        // We don't do anything on errors
        // these are expected to be duplicate DB records, enforced by a unique index
        // composited on symbol and date columns. This is until we can implement more complex unique validation.
        
        //Use for debug:
        //throw $e;
    }

    public function rules(): array
    {
        //I think that this will accept normal laravel model rules.
        //https://docs.laravel-excel.com/3.1/imports/validation.html
        //***
        //NOTE: I had to change the keys from column names to column indexes for these rules to work!
        return [
            //Examples of doing a unique validation on two columns:
            //- https://github.com/Maatwebsite/Laravel-Excel/issues/2709
            //- https://github.com/Maatwebsite/Laravel-Excel/issues/2872
            '0' => [
                'required','numeric', 
                /*
                TODO: Unique by date AND symbol
                'unique:market_data,NULL,'
                Rule::unique('market_data', '*.date', '*.symbol'), */
            ],
            '2'   => [
                'required', 'min:3', 'max:20',
                'in:BTC/USDT,ETH/USDT,XRP/USDT',
            ],
            '3'  => 'required|numeric|between:0,1000000',
            '4'  => 'required|numeric|between:0,1000000',
            '5'  => 'required|numeric|between:0,1000000',
            '6'  => 'required|numeric|between:0,1000000',
            '7'  => 'required|numeric',
            '8'  => 'required|numeric',
            '9'  => 'numeric|sometimes',
        ];
    }

    // https://stackoverflow.com/questions/57942366/laravel-excel-get-total-number-of-rows-before-import
    // https://docs.laravel-excel.com/3.1/architecture/objects.html#getters
    public function getRowCount(): int
    {
        return $this->rows;
    }
}
