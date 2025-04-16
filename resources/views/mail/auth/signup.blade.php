@component('mail::layout')

@slot ('header')
@component('mail::header', ['url' => config('app.url')])
    
@endcomponent
@endslot

@component('mail::message')

@component('mail::panel')
<h1 style="margin: 0 0 20px 0; color: #ffffff; font-size: 28px;"> Welcome to Ratefy</h1>
@endcomponent
<p style="margin-bottom: 15px;">Hi {{ $name }},</p>

<p style="margin-bottom: 15px;">Welcome to Ratefy – we’re excited to have you on board🤩</p>
<p style="margin-bottom: 15px;">We believe our platform will help you at every step of your journey as an online professional.
To ensure you gain the very best out of our service, we’ve put together some of the most helpful guides:</p>

<p style="margin-bottom: 15px;">This <a style="color: #cdcdcb; font-weight: bolder; text-decoration: none; letter-spacing: 2px;" href="https://www.youtube.com/@Ratefy">Video</a> walks you through setting up your Ratefy account for the first time</p>

<p style="margin-bottom: 15px;">Our <a style="color: #cdcdcb; font-weight: bolder; text-decoration: none; letter-spacing: 2px;" href="https://blog.ratefy.co/segment/ratefy-faqs">FAQ</a> is a great place to find the answers to common questions you might have as a new user. Our  <a style="color: #cdcdcb; font-weight: bolder; text-decoration: none; letter-spacing: 2px;" href="https://www.youtube.com/@Ratefy">Youtube channel</a> has a work-through on various ways you can use Ratefy. Our  <a style="color: #cdcdcb; font-weight: bolder; text-decoration: none; letter-spacing: 2px;" href="https://blog.ratefy.co">Blog</a> has some great tips and best practices to ensure your freelance journey is a success.</p>
<p style="margin-bottom: 15px;">Have any questions or need more information? Just <a style="color: #cdcdcb; font-weight: bolder; text-decoration: none; letter-spacing: 2px;" href="https://wa.link/5p4fbj">Contact us via WhatsApp!</a> We’re always here to help. </p>



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