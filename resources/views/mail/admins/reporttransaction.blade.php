
@component('mail::layout')

@slot ('header')
@component('mail::header', ['url' => config('app.url')])
    
@endcomponent
@endslot

@component('mail::message')

@component('mail::panel')
<h1 style="margin: 0 0 20px 0; color: #ffffff; font-size: 28px;"> Admin is now involved!</h1>
@endcomponent
<p style="margin-bottom: 15px;">Dear {{ $name }},</p>



<p style="margin-bottom: 15px;">To ensure this transaction goes as smoothly as possible, Ratefy admin is now involved in the transaction on the chat page.</p>



@component('mail::table')
|E-wallet options | Fiverr     |
| ------------- | :-----------: | 
| Payment option      | Paypal      | 
| Amount to send      | 100 USD |
| Amount to receive   | 135,240 NGN |

@endcomponent

<p style="margin-bottom: 15px;"> Kindly go to the transaction page and fulfill all requirements to be on the safer side. Kindly contact <a style="color: #cdcdcb; font-weight: bolder; text-decoration: none; letter-spacing: 2px;" href="https://wa.link/5p4fbj">  Support </a> if you have any concerns</p>


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