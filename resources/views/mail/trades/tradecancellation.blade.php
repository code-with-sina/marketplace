@component('mail::layout')

@slot ('header')
@component('mail::header', ['url' => config('app.url')])

@endcomponent
@endslot

@component('mail::message')

@component('mail::panel')
<h1 style="margin: 0 0 20px 0; color: #ffffff; font-size: 28px;"> Your Transaction Just Got Canceled</h1>
@endcomponent
<p style="margin-bottom: 15px;">Your transaction with the details below has been canceled by {{ $recipientname ?? 'Admin'}} </p>


@component('mail::table')
| Category | {{ $wallet }} |
| ------------- | :-----------: |
|Sub-category | {{ $paymentOptions}} |
| Quantity | {{ $amount }}  |
| Amount  | 135,240 NGN |

@endcomponent

<p style="margin-bottom: 15px;">Kindly check the transaction page to know why the trade was canceled. Please contact <a style="color: #cdcdcb; font-weight: bolder; text-decoration: none; letter-spacing: 2px;" href="mailto:chat@ratefy.co"> Ratefy support </a> ASAP if you have any concern</p>

@component('mail::button', ['url' => '' ])
Go to transaction page
@endcomponent


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