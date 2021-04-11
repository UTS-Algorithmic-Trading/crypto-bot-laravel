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
                    <canvas id="marketChart"></canvas>
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
        
        data["BTC"].forEach((pt) => {
            chart.data.datasets[0].data.push(pt);
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
                labels: ['Red', 'Blue', 'Yellow', 'Green', 'Purple', 'Orange'],
                datasets: [{
                    label: '# of Votes',
                    data: [12, 19, 3, 5, 2, 3],
                    backgroundColor: [
                        'rgba(255, 99, 132, 0.2)',
                        'rgba(54, 162, 235, 0.2)',
                        'rgba(255, 206, 86, 0.2)',
                        'rgba(75, 192, 192, 0.2)',
                        'rgba(153, 102, 255, 0.2)',
                        'rgba(255, 159, 64, 0.2)'
                    ],
                    borderColor: [
                        'rgba(255, 99, 132, 1)',
                        'rgba(54, 162, 235, 1)',
                        'rgba(255, 206, 86, 1)',
                        'rgba(75, 192, 192, 1)',
                        'rgba(153, 102, 255, 1)',
                        'rgba(255, 159, 64, 1)'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });

        console.log('Updating Chart with data');
        $.get("{{ route('market.chart_data') }}", 
        //Data returned:
        function (data) {
            console.log('Got new chart data');
            console.log(data);
            console.log(data.data["BTC"]);
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
