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

                <div>
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
    function updateData(chart, labels, data) {
        chart.clear();

        labels.forEach((lb) => {
            chart.data.labels.push(lb);
        });
        
        chart.data.datasets[0].label = "BTC - Binance";
        data["BTC - Binance"].forEach((pt) => {
            chart.data.datasets[0].data.push(pt);
        });

        //Add second series for FTX
        chart.data.datasets[1].label = "BTC - FTX";
        data["BTC - FTX"].forEach((pt) => {
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
        chart.data.labels.pop();
        chart.data.datasets.forEach((dataset) => {
            dataset.data.pop();
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

        console.log('Updating Chart with data');
        $.get("{{ route('market.chart_data') }}", 
        //Data returned:
        function (data) {
            console.log('Got new chart data');
            console.log(data);
            console.log(data.data["BTC - FTX"]);
            updateData(myChart, data.labels, data.data);
        })
        //Success:
        .done(function () {

        })
        //Failure:
        .fail(function () {
            console.log("Request for market data failed");
        });

        //Trigger the run arbitrage simulation
        $('#run-arbitrage').on('click', function (){
            $.get("{{ route('market.run_arbitrage_algorithm') }}",
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
            $.get("{{ route('market.run_arbitrage_algorithm_v2') }}",
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
