@component('mail::layout')

@slot ('header')
@component('mail::header', ['url' => config('app.url')])

@endcomponent
@endslot

@component('mail::message')

@component('mail::panel')
<h1 style="margin: 0 0 20px 0; color: #ffffff; font-size: 28px;"> Release Payment Authentication</h1>
@endcomponent
<p style="margin-bottom: 15px;">Hello {{ $firstname }} </p>

<p style="margin-bottom: 15px;">Please use the following "OTP" to complete your withdrawal </p>
<p style="margin-bottom: 15px;">OTP: {{ $code }}</p>
<p style="margin-bottom: 15px;">Please note, this OTP is valid for 10 minutes. If you didn't request this code, you can safely ignore this email.</p>
<p style="margin-bottom: 15px;">Thanks for choosing Ratefy</p>

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