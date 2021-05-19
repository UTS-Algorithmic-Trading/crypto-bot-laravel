<?php

namespace App\Http\Controllers;

use App\Models\MarketData;
use App\Imports\MarketDataImport;
use App\Providers\ArbitrageAlgorithmService;
use DateInterval;
use DateTimeZone;
use DateTimeImmutable;
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
    public function chartData()
    {
        //Set format of xAxis depending on interval used.
        $intervalHours = 1;
        $xFormat = 'Y-m-d H:i:s';

        //For now just set hourly.
        switch ($intervalHours)
        {
            default:
                $xFormat = 'd M H:i';
                break;
        }

        $tz = new DateTimeZone('UTC');
        $dtStart = new DateTimeImmutable('2020-11-22 00:00:00', $tz);
        $dtEnd = $dtStart->add(new DateInterval('PT'.$intervalHours.'H'));
        $rows = MarketData::where('symbol', 'BTC/USDT')
                    ->where('date', '>=', $dtStart)
                    ->where('date', '<=', $dtEnd)
                    ->where('exchange', 1)->get(); //Exchange 1 is binance


        $rowsFTX = MarketData::where('symbol', 'BTC/USDT')
                            ->where('date', '>=', $dtStart)
                            ->where('date', '<=', $dtEnd)
                            ->where('exchange', 2)->get(); //Exchange 2 is FTX

        $labels = []; //Labels is the x-axis values
        $data = [];
        //BTC is the base data series. Add others with a different key.
        //The key doubles as the series label
        $data['BTC - Binance'] = [];
        foreach ($rows as $row)
        {
            $labels[] = $row->date->format($xFormat);
            $data['BTC - Binance'][] = $row->close_price;
        }

        $data['BTC - FTX'] = [];
        foreach ($rowsFTX as $row)
        {
            //Only add labels the first time
            //$labels[] = $row->date;
            $data['BTC - FTX'][] = $row->close_price;
        }

        $val = [
            'labels' => $labels,
            'data' => $data,
        ];

        //ddd($val);
        return response()->json($val);
    }

    public function runArbitrageAlgorithm()
    {
        //Set format of xAxis depending on interval used.
        $intervalHours = 1;

        $tz = new DateTimeZone('UTC');
        $dtStart = new DateTimeImmutable('2020-11-22 00:00:00', $tz);
        $dtEnd = $dtStart->add(new DateInterval('PT'.$intervalHours.'H'));

        $service = new ArbitrageAlgorithmService($dtStart, $dtEnd);
        return response()->json($service->getData($dtStart, $dtEnd, 'BTC/USDT'));
    }


    public function runArbitrageAlgorithm_V2()
    {
        //Set format of xAxis depending on interval used.
        $intervalHours = 1;

        $tz = new DateTimeZone('UTC');
        $dtStart = new DateTimeImmutable('2020-11-22 00:00:00', $tz);
        $dtEnd = $dtStart->add(new DateInterval('PT'.$intervalHours.'H'));

        $service = new ArbitrageAlgorithmService($dtStart, $dtEnd);
        return response()->json($service->getDataV2($dtStart, $dtEnd, 'BTC/USDT'));
    }
}
