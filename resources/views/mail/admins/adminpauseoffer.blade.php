
@component('mail::layout')

@slot ('header')
@component('mail::header', ['url' => config('app.url')])
    
@endcomponent
@endslot

@component('mail::message')

@component('mail::panel')
<h1 style="margin: 0 0 20px 0; color: #ffffff; font-size: 28px;">  Offer has been paused</h1>
@endcomponent
<p style="margin-bottom: 15px;">Dear {{ $name }},</p>



<p style="margin-bottom: 15px;">Your offer with the details below has been paused due to trading inactivity.</p>



@component('mail::table')
|E-wallet options | Fiverr     |
| ------------- | :-----------: | 
| Payment option      | Paypal      | 
| Amount to send      | 100 USD |
| Amount to receive   | 135,240 NGN |

@endcomponent

<p style="margin-bottom: 15px;">To reactivate your offer, kindly log in to your account and toggle the offer activation button.  </p>
<p style="margin-bottom: 15px;">If you have more questions about your offers, feel free to <a style="color: #cdcdcb; font-weight: bolder; text-decoration: none; letter-spacing: 2px;" href="mailto:chat@ratefy.co">contact us</a> </p>

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