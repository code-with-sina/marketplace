
@component('mail::layout')

@slot ('header')
@component('mail::header', ['url' => config('app.url')])
    
@endcomponent
@endslot

@component('mail::message')

@component('mail::panel')
<h1 style="margin: 0 0 20px 0; color: #ffffff; font-size: 28px;">  Offer has been disabled</h1>
@endcomponent
<p style="margin-bottom: 15px;">Dear {{ $name }},</p>



<p style="margin-bottom: 15px;">Your offer with the details below has been disabled because it violates our terms and conditions. </p>



@component('mail::table')
|E-wallet options | Fiverr     |
| ------------- | :-----------: | 
| Payment option      | Paypal      | 
| Amount to send      | 100 USD |
| Amount to receive   | 135,240 NGN |

@endcomponent

<p style="margin-bottom: 15px;">kindly read through our terms and conditions before you create another offer. </p>
<p style="margin-bottom: 15px;">If you have any questions about your offers, feel free to contact us. </p>

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