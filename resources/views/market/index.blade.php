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
                    borderWidth: 1,

                },
                //Dataset1
                {
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
    });
</script>
@endsection
