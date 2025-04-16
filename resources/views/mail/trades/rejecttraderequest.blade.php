@component('mail::layout')

@slot ('header')
@component('mail::header', ['url' => config('app.url')])

@endcomponent
@endslot

@component('mail::message')

@component('mail::panel')
<h1 style="margin: 0 0 20px 0; color: #ffffff; font-size: 28px;"> Trade Request Rejected</h1>
@endcomponent
<p style="margin-bottom: 15px; color: #ffffff;">Dear {{ $user->firstname }}, Your trade request has been rejected.</p>


@component('mail::table')
| Category | {{ $wallet }} |
| ------------- | :-----------: |
|Sub-category | {{ $paymentOptions}} |
| Quantity | {{ $amount }}  |
| Amount | {{ $amountInNaira }} NGN |

@endcomponent

<p style="margin-bottom: 15px; color: #ffffff;">Kindly check other available offers or create your own offer to meet your needs.</p>

@component('mail::button', ['url' => 'https://market.ratefy.co/dashboard/overview'])
Go to Offers
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