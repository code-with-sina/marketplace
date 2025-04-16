@component('mail::layout')

@slot ('header')
@component('mail::header', ['url' => config('app.url')])

@endcomponent
@endslot

@component('mail::message')

@component('mail::panel')
<h1 style="margin: 0 0 20px 0; color: #ffffff; font-size: 28px;"> Go to the transaction page now!</h1>
@endcomponent
<p style="margin-bottom: 15px;">Dear {{ $user->firstname }}, The trade request has been accepted and your transaction has been initiated.</p>


@component('mail::table')
| Category | {{ $wallet }} |
| -------------------- | :-----------------------: |
|Sub-category | {{ $paymentOptions}} |
| Quantity | {{ $amount }}  |
| Amount  | {{ $amountInNaira }} NGN |

@endcomponent

<p style="margin-bottom: 15px;">Do not keep {{ $recipient->firstname }} waiting, Kindly go to the transaction page to continue with the transaction.</p>

@component('mail::button', ['url' => $url])
Go to Transactions
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