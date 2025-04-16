
@component('mail::layout')

@slot ('header')
@component('mail::header', ['url' => config('app.url')])
    
@endcomponent
@endslot

@component('mail::message')

@component('mail::panel')
<h1 style="margin: 0 0 20px 0; color: #ffffff; font-size: 28px;"> Your offer has been Approved</h1>
@endcomponent
<p style="margin-bottom: 15px;">Congratulations! </p>
<p style="margin-bottom: 15px;">Your new offer on Ratefy marketplace with the details below has been approved. The offer is now active and visible to users on Ratefy.</p>


@component('mail::table')
|E-wallet options       | Fiverr            |
| --------------------- | :----------------:| 
| Payment option        | Paypal            | 
| Range                 | 50 - 5,000 USD    |
| Exchange rate         | 1,545 per USD     |

@endcomponent

<p style="margin-bottom: 15px;">Kindly stay alert to attend to any transaction request that might come your way and ensure you maintain a high completion rate.</p>




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