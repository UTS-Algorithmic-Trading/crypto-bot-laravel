<?php

namespace App\Http\Controllers;

use App\Models\MarketData;
use App\Imports\MarketDataImport;
use App\Providers\ArbitrageAlgorithmService;
use DateInterval;
use DateTimeZone;
use DateTimeImmutable;
use DateTime;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class MarketDataController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return view('market.index');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('market.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:csv,txt,xlsx,xls|max:51200',
            'exchange' => 'required|numeric',
        ]);

        if (!$request->file())
            return back()->with('error', 'Unable to upload file');

        $fileName = time().'_'.$request->file->getClientOriginalName();
        $filePath = 'app/public/'.$request->file('file')->storeAs('uploads', $fileName, 'public');

        $import = new MarketDataImport;
        $import->exchange = $request->input('exchange');

        try {
            /* 
             * Tried using Laravel Excel package but v3.0 uses a separate model instead of Excel::load
             * https://stackoverflow.com/questions/49473098/call-to-undefined-method-maatwebsite-excel-excelload
             * https://medium.com/maatwebsite/laravel-excel-lessons-learned-7fee2812551
             * 
             * New way doesn't use a reader but instead I must define the Excel "model"
             * and then can return a collection from this.
             * 
             * https://docs.laravel-excel.com/3.1/imports/ 
             * https://docs.laravel-excel.com/3.1/imports/collection.html 
             *  
            Excel::load($filePath, function ($reader) {

                $rows = $reader->toArray();
                $validationErrors = [];

                if ($validationErrors = $this->_validateUpload($rows) !== TRUE)
                {
                    return back()->withErrors($validationErrors);
                }

                foreach ($rows as $row) {
                    MarketData::firstOrCreate($row);
                }
            });
            */
            Excel::import($import, storage_path($filePath));
        }
        catch (\Maatwebsite\Excel\Validators\ValidationException $e)
        {
            $errors = [];
            foreach ($e->failures() as $f)
            {
                $errors[] = 'Row '.$f->row().'['.$f->attribute().']: '.join(';', $f->errors());
            }
            return back()->withErrors($errors);
        }
        catch (\Exception $e)
        {
            throw $e;
            return back()->withErrors([
                'Failed to read data: '.$e->getMessage()
            ]);
        }

        return back()->with('success', 'Successfully uploaded file '.$fileName.' and imported '.$import->getRowCount().' rows');

    }

    /**
     * Return chart data
     *
     * @return \Illuminate\Http\Response
     */
    public function chartData(string $start_date, string $end_date, string $crypto_currency, string $base_currency)
    {
        $localTz = new DateTimeZone('Australia/Sydney');
        $utcTz = new DateTimeZone('UTC');
        $dtStart = new DateTime($start_date, $localTz);
        $dtEnd = new DateTime($end_date, $localTz);
        //Convert to UTC
        $dtStart->setTimezone($utcTz);
        $dtEnd->setTimezone($utcTz);

        //Set format of xAxis depending on interval used.
        $intervalHours = floor(($dtEnd->getTimestamp() - $dtStart->getTimestamp()) / 3600);
        $xFormat = 'Y-m-d H:i:s';

        //For now just set hourly.
        switch ($intervalHours)
        {
            default:
                $xFormat = 'd M H:i';
                break;
        }

        $symbol = $crypto_currency.'/'.$base_currency;

        $rows = MarketData::where('symbol', $symbol)
                    ->where('date', '>=', $dtStart)
                    ->where('date', '<=', $dtEnd)
                    ->where('exchange', 1)->get(); //Exchange 1 is binance


        $rowsFTX = MarketData::where('symbol', $symbol)
                            ->where('date', '>=', $dtStart)
                            ->where('date', '<=', $dtEnd)
                            ->where('exchange', 2)->get(); //Exchange 2 is FTX

        $labels = []; //Labels is the x-axis values
        $data = [];
        //BTC is the base data series. Add others with a different key.
        //The key doubles as the series label
        $data[$symbol.' - Binance'] = [];
        foreach ($rows as $row)
        {
            $labels[] = $row->date->format($xFormat);
            $data[$symbol.' - Binance'][] = $row->close_price;
        }

        $data[$symbol.' - FTX'] = [];
        foreach ($rowsFTX as $row)
        {
            //Only add labels the first time
            //$labels[] = $row->date;
            $data[$symbol.' - FTX'][] = $row->close_price;
        }

        $val = [
            'labels' => $labels,
            'data' => $data,
        ];

        //ddd($val);
        return response()->json($val);
    }

    public function runArbitrageAlgorithm(string $start_date, string $end_date, string $crypto_currency, string $base_currency)
    {
        $localTz = new DateTimeZone('Australia/Sydney');
        $utcTz = new DateTimeZone('UTC');
        $dtStart = new DateTime($start_date, $localTz);
        $dtEnd = new DateTime($end_date, $localTz);
        //Convert to UTC
        $dtStart->setTimezone($utcTz);
        $dtEnd->setTimezone($utcTz);

        $symbol = $crypto_currency.'/'.$base_currency;

        $service = new ArbitrageAlgorithmService($dtStart, $dtEnd);
        return response()->json($service->getData($dtStart, $dtEnd, $symbol));
    }


    public function runArbitrageAlgorithm_V2(string $start_date, string $end_date, string $crypto_currency, string $base_currency)
    {
        $localTz = new DateTimeZone('Australia/Sydney');
        $utcTz = new DateTimeZone('UTC');
        $dtStart = new DateTime($start_date, $localTz);
        $dtEnd = new DateTime($end_date, $localTz);
        //Convert to UTC
        $dtStart->setTimezone($utcTz);
        $dtEnd->setTimezone($utcTz);
        $symbol = $crypto_currency.'/'.$base_currency;

        //Set format of xAxis depending on interval used.
        $intervalHours = floor(($dtEnd->getTimestamp() - $dtStart->getTimestamp()) / 3600);

        $service = new ArbitrageAlgorithmService($dtStart, $dtEnd);
        return response()->json($service->getDataV2($dtStart, $dtEnd, $symbol));
    }
}
