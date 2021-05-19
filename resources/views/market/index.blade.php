@extends('layouts.app')

@section('template_title')
Market Data
 @endsection

@section('template_fastload_css')
@endsection

@section('content')

    <div class="container">
        <div class="row">
            <div class="col-12 col-lg-10 offset-lg-1">

                <p>View market data below</p>
                <div class="mb-3">
                    <label for="start_date" class="form_label">Start Date</label>
                    <input type="text" id="start_date" class="form-control" value="2020-11-22 10:00:00" />
                </div>
                <div class="mb-3">
                    <label for="end_date" class="form_label">End Date</label>
                    <input type="text" id="end_date" class="form-control" value="2020-11-22 11:00:00" />
                </div>
                <div class="mb-3">
                    <label for="currency_selector" class="form_label">Currency</label>
                    <select id="currency_selector" class="form-control">
                        <option value="BTC/USDT" selected="selected">Bitcoin</option>
                        <option value="ETH/USDT">Ethereum</option>
                        <option value="XRP/USDT">XRP</option>
                    </select>
                </div>                <div>
                    <canvas id="marketChart" width="95%" height="60%"></canvas>
                </div>
                <hr>
                <a href="#" class="btn btn-primary" id="run-arbitrage">Run Arbitrage</a>
                <p>This performs a buy and sell on competing Exchanges when the value at one exchange becomes higher or lower than the other</p>
                <br>
                <a href="#" class="btn btn-primary" id="run-arbitrage-v2">Run Arbitrage V2</a>
                <p>This performs a buy and sell on competing Exchanges when the value at one exchange any time the value at the two exchanges are not equal.</p>
                <hr>
                <div id="run-results">
                </div>
            </div>
        </div>
    </div>

@endsection

@section('footer_scripts')

<script>

    //Update data via AJAX JSON data.
    //https://stackoverflow.com/questions/49360165/chart-js-update-function-chart-labels-data-will-not-update-the-chart

    //Call updateData once we have the new labels and data sets.
    function updateData(chart, labels, data, currency) {
        chart.clear();

        labels.forEach((lb) => {
            chart.data.labels.push(lb);
        });
        
        chart.data.datasets[0].label = currency+" - Binance";
        data[currency+" - Binance"].forEach((pt) => {
            chart.data.datasets[0].data.push(pt);
        });

        //Add second series for FTX
        chart.data.datasets[1].label = currency+" - FTX";
        data[currency+" - FTX"].forEach((pt) => {
            chart.data.datasets[1].data.push(pt);
        });

        chart.update();
    }

    function addDataPoints(chart, newPts)
    {
        chart.data.datasets[2] = {
            label: 'Buy Binance',
            type: 'scatter',
            backgroundColor: 'rgba(42, 189, 86, 1)',
            borderColor: 'rgba(34, 128, 62, 1)',
            borderWidth: 5,
            data: []
        };

        chart.data.datasets[3] = {
            label: 'Sell Binance',
            type: 'scatter',
            backgroundColor: 'rgba(252, 48, 3, 1)',
            borderColor: 'rgba(224, 18, 18, 1)',
            borderWidth: 5,
            data: []
        };

        console.log("New points:");
        console.log(newPts);

        newPts.forEach((pt) => {

            if (pt['type'] == 'both')
            {
                chart.data.datasets[2].data.push(pt['buy_price']);
                chart.data.datasets[3].data.push(pt['sell_price']);
            }
            else if (pt['type'] == 'buy')
            {
                chart.data.datasets[2].data.push(pt['close_price']);
                chart.data.datasets[3].data.push(null);
            }
            else if (pt['type'] == 'sell')
            {
                chart.data.datasets[3].data.push(pt['close_price']);
                chart.data.datasets[2].data.push(null);
            }
            else
            {
                chart.data.datasets[2].data.push(null);
                chart.data.datasets[3].data.push(null);
            }


            /*
            if (idx < newPts['buy'].length && newPts['buy'][idx]['date'] == date)
                chart.data.datasets[2].data.push(newPt['close_price']);
            else
                chart.data.datasets[2].data.push(null); //Add empty value if no buy pt here
            */
        });
        chart.update();
    }

    function removeData(chart) {
        chart.clear();
        console.log("Removing data...");
        chart.data.labels = [];
        chart.data.datasets.forEach((dataset) => {
            dataset.data = [];
        });
        chart.update();
    }

    //Begin update
    $(document).ready(function () {
        var ctx = document.getElementById('marketChart');
        var myChart = new Chart(ctx, {
            type: 'line',
            data: {
                datasets: [
                //Dataset0
                {
                    type: 'line',
                    borderWidth: 1,

                },
                //Dataset1
                {
                    type: 'line',
                    borderWidth: 1,
                    backgroundColor: 'rgba(255, 99, 132, 0.2)',
                    borderColor: 'rgba(255, 99, 132, 1)'
                }]
            },
            //Enable zoom and pan: https://github.com/chartjs/chartjs-plugin-zoomn
            plugins: {
                zoom: {
                    // Container for pan options
                    pan: {
                        // Boolean to enable panning
                        enabled: true,

                        // Panning directions. Remove the appropriate direction to disable 
                        // Eg. 'y' would only allow panning in the y direction
                        mode: 'xy'
                    },

                    // Container for zoom options
                    zoom: {
                        // Boolean to enable zooming
                        enabled: true,

                        // Zooming directions. Remove the appropriate direction to disable 
                        // Eg. 'y' would only allow zooming in the y direction
                        mode: 'xy',
                    }
                }
            }
            /* ,
            options: {
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }*/
        });

        function updateChart(startDate, endDate, currency)
        {
            console.log('Updating Chart with data');
            console.log('Start Date: '+startDate);
            console.log('End Date: '+endDate);
            console.log('Currency: '+currency);
            //Currency is in format of BTC/USDT which will split to two parameters in the URL when being passed in the AJAX request.
            $.get("http://crypto.local/market/chart_data/"+encodeURIComponent(startDate)+"/"+encodeURIComponent(endDate)+"/"+currency, 
            //Data returned:
            function (data) {
                console.log('Got new chart data');
                console.log(data);
                console.log(data.data[currency+" - FTX"]);
                updateData(myChart, data.labels, data.data, currency);
            })
            //Success:
            .done(function () {

            })
            //Failure:
            .fail(function () {
                console.log("Request for market data failed");
            });
        }

        var startDate = $('#start_date');
        var endDate = $('#end_date');
        var currency = $('#currency_selector');

        //Update chart on page load
        updateChart(startDate.val(), endDate.val(), currency.val());

        //Also update on currency or date change.
        currency.on('change', function ()
        {
            removeData(myChart);
            updateChart(startDate.val(), endDate.val(), currency.val());
        });

        startDate.on('change', function ()
        {
            removeData(myChart);
            updateChart(startDate.val(), endDate.val(), currency.val());
        });

        endDate.on('change', function ()
        {
            removeData(myChart);
            updateChart(startDate.val(), endDate.val(), currency.val());
        });


        //Trigger the run arbitrage simulation
        $('#run-arbitrage').on('click', function (){
            $.get("http://crypto.local/market/run_arbitrage_algorithm/"+startDate+"/"+endDate+"/"+currency,
            function (data) {
                console.log("Got arbitrage data");
                console.log(data);
                addDataPoints(myChart, data['data']);
                //Update with simulation info
                console.log('Simulation done');
                console.log(data);
                console.log('Simulation profit: $'+data['simulation'].total_profit);
                console.log('Simulation profit in USDT: $'+data['profit_usdt']);
                var resultDiv = $('#run-results');
                resultDiv.html(
                    '<strong>Simulation finished running</strong>'+
                    '<p>Total profit in '+data['simulation'].currency+' is: '+data['simulation'].total_profit+'</p>'+
                    '<p>Total profit in USDT is '+data['profit_usdt']+'</p>'
                );
                /*
                //Get profit in USDT
                $.get("{* route('simulation.get_profit') *}"+"/"+data['simulation'].id,
                function (profit_data) {
                    console.log('Found profit:');
                    console.log(profit_data);
                    //Update UI with simulation profit.
                    console.log('Profit in USD is: $'+profit_data['profit_usdt']);
                });
                */
            })
            .fail(function () {
                console.log("Failed to get arbitrage data");
            });
        });


        //Trigger the run arbitrage V2 simulation
        $('#run-arbitrage-v2').on('click', function (){
            $.get("http://crypto.local/market/run_arbitrage_algorithm_v2/"+startDate+"/"+endDate+"/"+currency,
            function (data) {
                console.log("Got arbitrage data");
                console.log(data);
                addDataPoints(myChart, data['data']);
                //Update with simulation info
                console.log('Simulation done');
                console.log(data);
                console.log('Simulation profit: $'+data['simulation'].total_profit);
                console.log('Simulation profit in USDT: $'+data['profit_usdt']);
                var resultDiv = $('#run-results');
                resultDiv.html(
                    '<strong>Simulation '+data['simulation'].algorithm_name+' finished running</strong>'+
                    '<p>Total profit in '+data['simulation'].currency+' is: '+data['simulation'].total_profit+'</p>'+
                    '<p>Total profit in USDT is '+data['profit_usdt']+'</p>'
                );
                /*
                //Get profit in USDT
                $.get("{* route('simulation.get_profit') *}"+"/"+data['simulation'].id,
                function (profit_data) {
                    console.log('Found profit:');
                    console.log(profit_data);
                    //Update UI with simulation profit.
                    console.log('Profit in USD is: $'+profit_data['profit_usdt']);
                });
                */
            })
            .fail(function () {
                console.log("Failed to get arbitrage data");
            });
        });
    });
</script>
@endsection
