<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        {{-- CSRF Token --}}
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>@hasSection('template_title')@yield('template_title') | @endif {{ config('app.name', Lang::get('titles.app')) }}</title>
        <meta name="description" content="">
        <meta name="author" content="Jeremy Kenedy">
        <link rel="shortcut icon" href="/favicon.ico">

        {{-- HTML5 shim and Respond.js for IE8 support of HTML5 elements and media queries --}}
        <!--[if lt IE 9]>
            <script src="https://oss.maxcdn.com/html5shiv/3.7.2/html5shiv.min.js"></script>
            <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
        <![endif]-->

        {{-- Fonts --}}
        @yield('template_linked_fonts')

        {{-- Styles --}}
        <link href="{{ mix('/css/app.css') }}" rel="stylesheet">

        @yield('template_linked_css')

        <style type="text/css">
            @yield('template_fastload_css')

            @if (Auth::User() && (Auth::User()->profile) && (Auth::User()->profile->avatar_status == 0))
                .user-avatar-nav {
                    background: url({{ Gravatar::get(Auth::user()->email) }}) 50% 50% no-repeat;
                    background-size: auto 100%;
                }
            @endif

        </style>

        {{-- Scripts --}}
        <script>
            window.Laravel = {!! json_encode([
                'csrfToken' => csrf_token(),
            ]) !!};
        </script>

        @if (Auth::User() && (Auth::User()->profile) && $theme->link != null && $theme->link != 'null')
            <link rel="stylesheet" type="text/css" href="{{ $theme->link }}">
        @endif

        @yield('head')
        @include('scripts.ga-analytics')
    </head>
    <body>
        <div id="app">

            @include('partials.nav')

            <main class="py-4">

                <div class="container">
                    <div class="row">
                        <div class="col-12">
                            @include('partials.form-status')
                        </div>
                    </div>
                </div>

                @yield('content')

            </main>

        </div>

        {{-- Delete modal --}}
        <div class="d-flex justify-content-center align-items-center w-100">
            <div id="deleteModal" class="toast hide" role="alert" aria-live="assertive" aria-atomic="true" data-autohide="false">
                <div class="toast-body">
                    Are you sure you want to delete this item?
                    <div class="mt-2 pt-2 border-top">
                        <button type="button" class="btn btn-primary btn-sm" id="close-delete-modal-btn"  data-dismiss="toast">Close</button>
                        <button type="button" class="btn btn-danger btn-sm" id="do-delete-modal-btn" data-deleteme="none">Yes, delete</button>
                    </div>
                </div>
            </div>
        </div>

        {{-- Scripts --}}
        <script src="{{ mix('/js/app.js') }}"></script>

        @if(config('settings.googleMapsAPIStatus'))
            {!! HTML::script('//maps.googleapis.com/maps/api/js?key='.config("settings.googleMapsAPIKey").'&libraries=places&dummy=.js', array('type' => 'text/javascript')) !!}
        @endif

        <script type="text/javascript">
        $(document).ready(function () {
            $('#close-delete-modal-btn').click(function () {
                console.log("Resetting delete modal");
                $(this).data('deleteme', "none");
            });


            $('#deleteBtn').click(function () {
                console.log("Opening delete modal");
                let id = $(this).data('deleteme');
                console.log('Settimng deleteme to: '+id);
                $('#do-delete-modal-btn').data('deleteme', id);
                $('#deleteModal').toast('show');

            });
            $('#do-delete-modal-btn').click(function () {
                let id = $(this).data('deleteme');
                if (id === "none")
                {
                    console.log("Skipping delete of: "+id);
                    return;
                }
                console.log("Attempting delete of: "+id);
                let token  = $('meta[name="csrf-token"]').attr('content');
                console.log('CSRF Token: '+token);

                $.ajax({
                    type: 'DELETE',
                    url: id,
                    data: {
                        _token: token,
                        _method: 'DELETE',
                    },
                    success: function() {
                        console.log('Successfully deleted item');
                        let parts = id.split('/');
                        console.log('Root part is: '+parts[1]);
                        console.log('Redirecting to: '+window.location.href.replace(id, parts[1]));
                        window.location.href = parts[1];
                    }
                })

            });
        });
        </script>

        @yield('footer_scripts')

    </body>
</html>
