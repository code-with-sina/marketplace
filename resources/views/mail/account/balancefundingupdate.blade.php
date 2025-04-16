
@component('mail::layout')

@slot ('header')
@component('mail::header', ['url' => config('app.url')])
    
@endcomponent
@endslot

@component('mail::message')

@component('mail::panel')
<h1 style="margin: 0 0 20px 0; color: #ffffff; font-size: 28px;"> Credit alert!</h1>
@endcomponent
<p style="margin-bottom: 15px;">The Ratefy wallet has been credited with the sum of {{ $amount }}. Kindly log in to check your new balance. </p>

@component('mail::button', ['url' => '' ])
Check balance
@endcomponent

<p style="margin-bottom: 15px;">Thanks for choosing Ratefy.</p>



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