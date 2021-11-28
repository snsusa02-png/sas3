
@extends('layouts.app')

@section('content')
{{--    #@guest--}}
{{--    #@else--}}
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-8">
                    <h1>{{$org->name}}</h1>

                    <hr>
                    <a href="/orgs" >назад</a>
                </div>
            </div>
        </div>
{{--    @endguest--}}
@endsection

