@extends('layouts.app')

@section('template_title')
    Create a new bot
@endsection

@section('template_fastload_css')
@endsection

@section('content')

    <div class="container">
        <div class="row">
            <div class="col-12 col-lg-10 offset-lg-1">
                <p>Use this page to upload historical data, but take note of the rules below. We don't have validation logic for all of this.</p>
                <p>The upload tool will try and skip over any rows that have already been uploaded for a given date timestamp, and skip rows with validation errors.</p>
                <div class="alert alert-danger" role="alert">
                    <ul>
                        <li><strong>Upload data from here</strong>, specifically per minute data sets: <a href="https://www.cryptodatadownload.com/data/binance/">https://www.cryptodatadownload.com/data/binance/</a></li>
                        <li>Make sure you upload data <strong>oldest rows to newest rows, descending</strong></li>
                        <li><strong>Make sure you remove the heading row</li>
                    </ul>
                </div>

                <form method="POST" action="{{ route('market.store') }}" id="create-form" enctype="multipart/form-data">
                    @csrf

                    <div class="mb-3">
                        <label for="exchange">Select exchange</label>
                        <select name="exchange" id="exchange" class="form-select form-control">
                            <option value="1">Binance</option>
                            <option value="2">FTX</option>
                        </select>
                    </div>

                    <!-- Submit Field -->
                    <label>Upload file for exchange</label>
                    <div class="form-group col pl-0">
                        <div class="custom-file">
                            <input type="file" name="file" class="custom-file-input" id="chooseFile">
                            <label class="custom-file-label" for="chooseFile">Select file</label>
                        </div>
                        <hr>
                        <button type="submit" class="btn btn-primary btn-block mb-3">Upload</button>
                        <a href="{{ route('market.index') }}" class="btn btn-danger mb-3">Cancel</a>
                    </div>
                </form>


            </div>
        </div>
    </div>

@endsection

@section('footer_scripts')
@endsection
