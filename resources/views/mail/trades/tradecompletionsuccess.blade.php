@component('mail::layout')

@slot ('header')
@component('mail::header', ['url' => config('app.url')])

@endcomponent
@endslot

@component('mail::message')

@component('mail::panel')
<h1 style="margin: 0 0 20px 0; color: #ffffff; font-size: 28px;"> Transaction completed successfully✅</h1>
@endcomponent
<p style="margin-bottom: 15px;">Your transaction has been completed. </p>


@component('mail::table')
| Category | {{ $wallet_name }} |
| ------------- | :-----------: |
|Sub-category | {{ $paymentOptions}} |
| Quantity | {{ $amount }}  |
| Amount  | {{ $amount_to_receive }} NGN |

@endcomponent

<p style="margin-bottom: 15px;">You can rate your experience with {{ $recipient }} on the transaction page</p>
<p style="margin-bottom: 15px;">Thanks for choosing Ratefy</p>



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