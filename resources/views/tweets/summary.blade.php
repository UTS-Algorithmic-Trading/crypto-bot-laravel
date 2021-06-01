@extends('layouts.app')

@section('template_title')
    Social media crypto sentiment
@endsection

@section('template_fastload_css')
@endsection

@section('content')

    <div class="container">
        <div class="row">
            <div class="col-12 col-lg-10 offset-lg-1">
                <h2>Social Sentiment Rating</h2>
                <p>Use this page to see the current or historical sentiment of social media towards a given currency. This can indicate whether you should buy or sell. Or take a more or less aggressive approach.</p>
                <h3>Authors</h3>
                <p>You are following these twitter users:</p>
                <ul>
                @foreach ($authors as $author)
                    <li><a href="https://twitter.com/{{ $author->screen_name }}">{{ '@'.$author->screen_name }}</a> - {{ $author->name }}</li>
                @endforeach
                </ul>
                <table class="table">
                    @foreach ($sentiment as $currency => $data)
                    <thead>
                        <th colspan="4">
                            {{ $currency }} - Sentiment scores
                        </th>
                    </thead>
                    <tr>
                        <td><strong>Rating</strong></td>
                        <td>
                            @if ($data['rating'] == 'highly positive')
                                <span class="badge bg-success">{{ $data['rating'] }}</span>
                            @elseif ($data['rating'] == 'positive')
                                <span class="badge bg-success text-dark" style="background-color: rgb(164, 204, 100)!important;">{{ $data['rating'] }}</span>
                            @elseif ($data['rating'] == 'neutral')
                                <span class="badge bg-info text-dark" style="background-color: rgb(151, 202, 230)!important;">{{ $data['rating'] }}</span>
                            @elseif ($data['rating'] == 'highly negative')
                                <span class="badge bg-danger">{{ $data['rating'] }}</span>
                            @elseif ($data['rating'] == 'negative')
                                <span class="badge bg-danger text-dark" style="background-color: rgb(204, 100, 100)!important;">{{ $data['rating'] }}</span>
                            @endif
                        </td>
                        <td><strong>Total</strong></td>
                        <td>{{ $data['total'] }}</td>
                    </tr>
                    <tr>
                        <td><strong>Average</strong></td>
                        <td>{{ $data['average'] }}</td>
                        <td><strong>Count</strong></td>
                        <td>{{ $data['count'] }}</td>
                    </tr>
                    <tr>
                        <td colspan="4"><a href="#" class="btn btn-secondary toggle-tweets-table">Show Tweets</a></td>
                    </tr>
                    <tr>
                        <td colspan="4" class="tweets-table" style="display: none;">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <th colspan="2">Tweet</th>
                                    <th colspan="2">Score</th>
                                </thead>
                                @foreach ($data['scores'] as $tweet)
                                <tr>
                                    <td colspan="2">{{ $tweet['tweet'] }}</td>
                                    <td colspan="2">{{ $tweet['sentiment'] }}</td>
                                </tr>
                                @endforeach
                            </table>
                        </td>
                    </tr>
                    @endforeach
                </table>

                <hr>
                <h2>Synchronisation Tool</h2>
                <p>Use this tool to synchronise new tweets for all followed authors. Pick a currency, and this will perform semantic analysis on relevant tweets specific to the currency. This will impact the overall rating of the currency.</p>
                <div class="mb-3">
                    <label for="currency_selector" class="form_label">Currency</label>
                    <select id="currency_selector" class="form-control">
                        <option value="BTC" selected="selected">Bitcoin</option>
                        <option value="ETH">Ethereum</option>
                        <option value="XRP">XRP</option>
                    </select>
                </div>
                <a href="#" class="btn btn-primary" id="sync-tweets">Synchronise Tweets</a>
                <br>
                <br>
                <div id="sync-result"></div>
            </div>
        </div>
    </div>

@endsection

@section('footer_scripts')
<script type="text/javascript">
    $(document).ready(function (e) {
        $('.toggle-tweets-table').on('click', function (e) {
            console.log('Toggling tweets table');
            var nextRow = $(this).closest('tr').next('tr');
            console.log('Next row:');
            console.log(nextRow);
            var el = nextRow.find('.tweets-table');
            console.log(el);
            if (el.is(":visible"))
            {
                //If currently visible we are about to hide.
                $(this).text("Show Tweets");
            }
            else
            {
                $(this).text("Hide Tweets");
            }
            el.toggle(400);
            e.preventDefault();
        });

        $('#sync-tweets').on('click', function (e) {
            e.preventDefault();
            console.log('Syncing tweets');
            $('body').addClass("loading");
            $.get("https://crypto.rh.ys.id.au/tweets/sync/"+encodeURIComponent($('#currency_selector').val()), function (data) {
                console.log('Finished sync');
                console.log(data);
                $('body').removeClass("loading");
                $('#sync-result').html(
                    '<div class="alert alert-success" role="alert">'+
                    '<strong>Sync finished!</strong>'+
                    '<ul>'+
                    '<li>New tweets: '+data.new_tweet_count+'</li>'+
                    '<li>Rated relevant tweets: '+data.nlp_rated_count+'</li>'+
                    '</div>'  
                );
            })
            .fail(function () {
                $('body').removeClass("loading");
                $('#sync-result').html(
                    '<div class="alert alert-danger" role="alert">'+
                    '<strong>Sync failed!</strong>'+
                    '</div>'
                );
            });
        });
    });

</script>
@endsection
