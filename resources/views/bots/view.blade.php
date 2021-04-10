@extends('layouts.app')

@section('template_title')
    Bot - {{ $bot->name }}
@endsection

@section('template_fastload_css')
@endsection

@section('content')

    <div class="container">
        <div class="row">
            <div class="col-12 col-lg-10 offset-lg-1">

                <p>Bot details:</p>
                <table class="table table-striped table-hover">
                    <tbody>
                        <tr>
                            <td>Name</td>
                            <td>{{ $bot->name }}</td>

                        </tr>
                        <tr>
                            <td>Currency</td>
                            <td>{{ $bot->stock_type }}</td>
                        </tr>
                        <tr>
                            <td>Trade Started</td>
                            <td>{{ $bot->trade_started_at }}</td>
                        </tr>
                        <tr>
                            <td>Trade Ended</td>
                            <td>{{ $bot->trade_ended_at }}</td>
                        </tr>
                        <tr>
                            <td>Initial Trade</td>
                            <td>${{ $bot->initial_bet }}</td>
                        </tr>
                        <tr>
                            <td>Max Total Trade</td>
                            <td>${{ $bot->max_bet }}</td>
                        </tr>
                        <tr>
                            <td>Target Profit (%)</td>
                            <td>{{ $bot->target_profit_percent }}%</td>
                        </tr>
                        <tr>
                            <td>Trailing Profit (%)</td>
                            <td>{{ $bot->trailing_profit_percent }}%</td>
                        </tr>
                        <tr>
                            <td>Stop Loss (%)</td>
                            <td>{{ $bot->target_profit_percent }}%</td>
                        </tr>
                        <tr>
                            <td>Current High Price</td>
                            <td>${{ $bot->current_high_price }}</td>
                        </tr>
                        <tr>
                            <td>Current Low Price</td>
                            <td>${{ $bot->current_low_price }}</td>
                        </tr>
                    </tbody>
                </table>
                <a href="{{ route('bots.index') }}" class="btn btn-primary mb-3">Back</a>
            </div>
        </div>
    </div>

@endsection

@section('footer_scripts')
@endsection
