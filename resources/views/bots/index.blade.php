@extends('layouts.app')

@section('template_title')
{{ auth()->user()->name }}'s Bots
 @endsection

@section('template_fastload_css')
@endsection

@section('content')

    <div class="container">
        <div class="row">
            <div class="col-12 col-lg-10 offset-lg-1">

                <a href="{{ route('bots.create') }}" class="btn btn-primary">Create a bot</a>
                <br><br>
                <p>These are your current bots</p>


                <table class="table table-striped table-hover">
                    <thead>
                        <th></th>
                        <th>Name</th>
                        <th>Currency</th>
                        <th>Target Profit</th>
                        <th>Started</th>
                    </thead>
                    <tbody>
                        @foreach ($bots as $bot)
                            <tr>
                                <td>
                                    <div class="btn-group" role="group">
                                        <a class="btn btn-secondary" href="{{ route('bots.show', $bot->id) }}">View</a>
                                        <a class="btn btn-primary" href="{{ route('bots.edit', $bot->id) }}">Edit</a>
                                    </div>
                                    <a class="btn btn-danger" id="deleteBtn" data-deleteme="{{ '/bots/'.$bot->id }}" href="#">Delete</a>
                                </td>
                                <td>{{ $bot->name }}</td>
                                <td>{{ $bot->stock_type }}</td>
                                <td>{{ $bot->target_profit_percent }}</td>
                                <td>{{ $bot->trade_started_at }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

@endsection

@section('footer_scripts')
@endsection
