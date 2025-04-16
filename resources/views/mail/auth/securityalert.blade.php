
@component('mail::layout')

@slot ('header')
@component('mail::header', ['url' => config('app.url')])
    
@endcomponent
@endslot

@component('mail::message')

@component('mail::panel')
<h1 style="margin: 0 0 20px 0; color: #ffffff; font-size: 28px;"> Your password has changed</h1>
@endcomponent
<p style="margin-bottom: 15px;">Hi {{ $name }},</p>



Your account password has just been changed. If you are not the one that changed the password, please   <a style="color: #cdcdcb; font-weight: bolder; text-decoration: none; letter-spacing: 2px;" href="mailto:chat@ratefy.co">let us know immediately.</a></p>


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