@component('mail::layout')

@slot ('header')
@component('mail::header', ['url' => config('app.url')])
    
@endcomponent
@endslot

@component('mail::message')

@component('mail::panel')
<h1 style="margin: 0 0 20px 0; color: #ffffff; font-size: 28px;"> Reset your password</h1>
@endcomponent
<p style="margin-bottom: 15px;">Hi {{ $name }},</p>

<p style="margin-bottom: 15px;">You recently requested resetting your Ratefy account password. Click the button below to proceed.</p>

@component('mail::button', ['url' => ''])
Reset Password
@endcomponent
<p style="margin-bottom: 15px;">If you didn’t make this request, then you can ignore this email.</p>

<p style="margin-bottom: 15px;">This password reset link is only valid for the next 30 minutes.</p>





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