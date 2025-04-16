
@component('mail::layout')

@slot ('header')
@component('mail::header', ['url' => config('app.url')])
    
@endcomponent
@endslot

@component('mail::message')

@component('mail::panel')
<h1 style="margin: 0 0 20px 0; color: #ffffff; font-size: 28px;"> Oops, Your offer is Rejected</h1>
@endcomponent
<p style="margin-bottom: 15px;">Your offer on Ratefy marketplace with the details below has been rejected.</p>



@component('mail::table')
|E-wallet options       | Fiverr            |
| --------------------- | :----------------:| 
| Payment option        | Paypal            | 
| Range                 | 50 - 5,000 USD    |
| Exchange rate         | 1,545 per USD     |

@endcomponent

<p style="margin-bottom: 15px;">It doesn't follow our offer creation guidelines. Kindly check our offer creation guidelines before recreating the offer. Kindly <a style="color: #cdcdcb; font-weight: bolder; text-decoration: none; letter-spacing: 2px;" href="mailto:chat@ratefy.co"> contact support </a> if you have any concerns. </p>




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