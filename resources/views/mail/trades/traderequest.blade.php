@component('mail::layout')

@slot ('header')
@component('mail::header', ['url' => config('app.url')])

@endcomponent
@endslot

@component('mail::message')

@component('mail::panel')
<h1 style="margin: 0 0 20px 0; color: #ffffff; font-size: 28px;"> New Trade Request</h1>
@endcomponent
<p style="margin-bottom: 15px; color: #ffffff;">Take Action {{ $user->firstname }},</p>

<p style="margin-bottom: 15px; color: #ffffff;">{{ $recipient->firstname }} has requested to trade with you. </p>


@component('mail::table')
| Category | {{ $wallet }} |
| --------------------- | :-----------------------: |
|Sub-category | {{ $paymentOptions}} |
| Quantity | {{ $amount }}  |
| Amount | {{ $amountInNaira }} NGN |

@endcomponent

<p style="margin-bottom: 15px; color: #ffffff;">kindly go to your dashboard to review and take action. The trade request will be automatically canceled in 30 minutes if you don't take action and will affect your completion rate.</p>

@component('mail::button', ['url' => 'https://market.ratefy.co/dashboard/overview' ])
Take Action Now!
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