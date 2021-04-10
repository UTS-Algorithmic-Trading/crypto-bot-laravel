@extends('layouts.app')

@section('template_title')
    Edit bot - {{ $bot->name }}
@endsection

@section('template_fastload_css')
@endsection

@section('content')

    <div class="container">
        <div class="row">
            <div class="col-12 col-lg-10 offset-lg-1">

                <form method="POST" action="{{ route('bots.update', $bot->id) }}" id='edit-form'>
                    @csrf
                    @method('PATCH')
                    @include('bots.fields')

                    <!-- Submit Field -->
                    <div class="form-group col pl-0">
                        <button type="submit" class="btn btn-primary mb-3">Edit</button>
                        <a href="{{ route('bots.index') }}" class="btn btn-danger mb-3">Cancel</a>
                    </div>
                </form>


            </div>
        </div>
    </div>

@endsection

@section('footer_scripts')
@endsection
