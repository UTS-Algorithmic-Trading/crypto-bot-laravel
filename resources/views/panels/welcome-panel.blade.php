@php

    $levelAmount = 'level';

    if (Auth::User()->level() >= 2) {
        $levelAmount = 'levels';

    }

@endphp

<div class="card">
    <div class="card-header @role('admin', true) bg-secondary text-white @endrole">

        Welcome {{ Auth::user()->name }}

        @role('admin', true)
            <span class="pull-right badge badge-primary" style="margin-top:4px">
                Admin Access
            </span>
        @else
            <span class="pull-right badge badge-warning" style="margin-top:4px">
                User Access
            </span>
        @endrole

    </div>
    <div class="card-body">
        <h2 class="lead">
            {{ trans('auth.loggedIn') }}
        </h2>
        <p>This application is designed to help you decide what strategies work best against different crypto currencies, based on historical data.</p>
        <p>To get started: <strong>Go to Market > View Market Data</strong></p>
        <ul>
            <li>Look at the graph, change the start and end dates to view different time ranges</li>
            <li>Change the currency to view different currencies</li>
            <li>Click the blue buttons below the graph to simulate different trading strategies.</li>
            <li>Look at the summary below to see how much profit would have been gained and use this to compare which strategies or currencies are most effective during that time period</li>
        </ul>
        <p>Look at the <strong>Tweets</strong> menu to see how social media is influencing a particular currency. Add more influencers as desired</p>
        <p>If you are an admin, use the <strong>Market > Upload Historical Data</strong> to add more data for different currencies.</p>
    </div>
</div>
