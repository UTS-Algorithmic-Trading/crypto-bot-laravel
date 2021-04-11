<?php

namespace App\Http\Controllers;

use App\Models\MarketData;
use App\Imports\MarketDataImport;
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
        ]);

        if (!$request->file())
            return back()->with('error', 'Unable to upload file');

        $fileName = time().'_'.$request->file->getClientOriginalName();
        $filePath = 'app/public/'.$request->file('file')->storeAs('uploads', $fileName, 'public');

        $import = new MarketDataImport;

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
}
