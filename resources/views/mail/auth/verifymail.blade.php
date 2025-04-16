@component('mail::layout')

@slot ('header')
@component('mail::header', ['url' => config('app.url')])
    
@endcomponent
@endslot

@component('mail::message')

@component('mail::panel')
<h1 style="margin: 0 0 20px 0; color: #ffffff; font-size: 28px;"> Verify your account </h1>
@endcomponent
<p style="margin-bottom: 15px;">Dear {{ $user->firstname }},</p>

<p style="margin-bottom: 15px;">Thank you for registering with Ratefy! To complete your registration and activate your account, please verify your email address by clicking the link below:</p>


@component('mail::button', ['url' => $url ])
Verify Your account
@endcomponent
<p style="margin-bottom: 15px;">If you did not create an account with Ratefy, please ignore this email. If you need any assistance, please contact our <a style="color: #cdcdcb; font-weight: bolder; text-decoration: none; letter-spacing: 2px;" href="https://wa.link/5p4fbj">support team on WhatsApp.</a></p>


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