@component('mail::layout')

@slot ('header')
@component('mail::header', ['url' => config('app.url')])
    
@endcomponent
@endslot

@component('mail::message')

@component('mail::panel')
<h1 style="margin: 0 0 20px 0; color: #ffffff; font-size: 28px;">  {!! $content!!}</h1>
@endcomponent
<p style="margin-bottom: 15px;">Dear Admin, <br> From {{ $fromUser}}</p>



<p style="margin-bottom: 15px;">{{ $direction}} </p>

<p style="margin-bottom: 15px;">This is a prompt alert </p>

@endcomponent

@slot('subcopy')
@component('mail::subcopy')

@endcomponent
@endslot


@slot ('footer')
@component('mail::footer')
@endcomponent
@endslot
@endcomponent