
@component('mail::layout')

@slot ('header')
@component('mail::header', ['url' => config('app.url')])
    
@endcomponent
@endslot

@component('mail::message')

@component('mail::panel')
<h1 style="margin: 0 0 20px 0; color: #ffffff; font-size: 28px;"> Your password was reset</h1>
@endcomponent
<p style="margin-bottom: 15px;">Hi {{ $name }},</p>

<p style="margin-bottom: 15px;">This message is to confirm that your Ratefy account password has been successfully reset.</p>


<p style="margin-bottom: 15px;">If you did not request a password change, please <a style="color: #cdcdcb; font-weight: bolder; text-decoration: none; letter-spacing: 2px;" href="mailto:chat@ratefy.co">contact us immediately</a>.</p>







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