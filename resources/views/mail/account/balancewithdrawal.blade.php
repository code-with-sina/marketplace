
@component('mail::layout')

@slot ('header')
@component('mail::header', ['url' => config('app.url')])
    
@endcomponent
@endslot

@component('mail::message')

@component('mail::panel')
<h1 style="margin: 0 0 20px 0; color: #ffffff; font-size: 28px;"> Your money is on its way🥳</h1>
@endcomponent
<p style="margin-bottom: 15px;">Hello {{ $firstname }},</p>
<p style="margin-bottom: 15px;">You've successfully withdrawn the amount of {{ $amount }} from your Ratefy wallet. Your money should be in your bank account anytime soon. Kindly <a style="color: #cdcdcb; font-weight: bolder; text-decoration: none; letter-spacing: 2px;" href="mailto:chat@ratefy.co"> contact </a> us if you have any questions.</p>





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