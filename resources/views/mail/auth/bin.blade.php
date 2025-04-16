<!DOCTYPE html>
<html lang="en">

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">

    <meta http-equiv="X-UA-Compatible" content="IE=edge">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link rel="icon" href="images/favicon.png" type="image/x-icon">

    <title> Hello  {{ $name }} </title>

    <link
        href="https://fonts.googleapis.com/css2?family=Rubik:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap"
        rel="stylesheet">



    <style type="text/css">
        body {
            text-align: center;
            margin: 0 auto;
            width: 650px;
            font-family: 'Rubik', sans-serif;
            background-color: #e2e2e2;
            display: block;
        }

        ul {
            margin: 0;
            padding: 0;
        }

        li {
            display: inline-block;
            text-decoration: unset;
        }

        a {
            text-decoration: none;
        }

        h5 {
            margin: 10px;
            color: #777;
        }

        .text-center {
            text-align: center
        }

        .main-bg-light {
            background-color: #f5f5f5;
        }

        h4.title {
            color: white;
            font-weight: bold;
            padding-bottom: 0;
            text-transform: capitalize;
            display: inline-block;
            letter-spacing: 1.5px;
            position: relative;
            padding-bottom: 5px;
            border-bottom: 2px solid white;
        }

        .header .header-logo a {
            display: block;
            margin: 0;
            padding: 20px;
            text-align: left;
        }

        .header .header-contain h5 {
            margin: 0;
            font-size: 20px;
            letter-spacing: 4px;
            font-weight: 800;
            text-transform: uppercase;
        }

        .header .header-contain h2 {
            margin: 40px 0 0;
            font-size: 28px;
            letter-spacing: 1px;
            text-transform: uppercase;
            position: relative;
            display: inline-block;
            padding-bottom: 18px;
        }

        .header .header-contain h2::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 2px;
            background-color: #212529;
        }

        .header .header-contain .market {
            margin: 25px auto 0;
            letter-spacing: 0;
            font-weight: normal;
            text-transform: none;
            display: block;
            width: 80%;
            color: #7e7e7e;
        }

        .title-2 h2 {
            margin: 0;
            font-size: 26px;
            letter-spacing: 1px;
            font-weight: 800;
            text-transform: uppercase;
            line-height: 1;
        }

        .title-2 h6 {
            font-size: 18px;
            font-weight: normal;
            margin: 0;
        }

        .title-2 button {
            color: white;
            letter-spacing: 1px;
            text-transform: capitalize;
            margin-top: 25px;
            border-radius: 8px;
            padding: 18px 30px;
            border: 1px solid #e22454;
            background-color: #e22454;
            font-size: 14px;
        }

        .header .header-contain button {
            text-transform: uppercase;
            margin: 25px 0;
            border-radius: 5px;
            padding: 15px 35px;
            background-color: #e22454;
            color: white;
            border: none;
        }

        .contact-table {
            width: 100%;
            margin-top: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .contact-table td {
            margin-top: 17px;
            position: relative;
            font-size: 13px;
            text-transform: uppercase;
            color: #ddd;
            letter-spacing: 1.1px;
        }

        .contact-table td:after {
            content: '';
            position: absolute;
            top: 50%;
            left: -10px;
            border-radius: 50%;
            background-color: white;
            width: 3px;
            height: 3px;
            transform: translateY(-50%);
        }

        .contact-table td:first-child:after {
            content: unset;
        }
    </style>
</head>

<body style="margin: 5.5% auto;">




    <table align="center" border="0" cellpadding="0" cellspacing="0"
        style="background-color: white; width: 90%; box-shadow: 0px 0px 14px -4px rgba(0, 0, 0, 0.2705882353);-webkit-box-shadow: 0px 0px 14px -4px rgba(0, 0, 0, 0.2705882353); padding: 45px;">
        <tbody>
            <tr>
                <td>
                    <table align="center" border="0" cellpadding="0" cellspacing="0" width="100%">  
                        <tr class="header">
                            <td align="center" class="header-logo"
                                style="text-align: center; display: block; margin-bottom: 20px; background-color: #181C1F;" valign="top" width="100%">
                              
                            </td>
                            <td class="header-contain" style="display: block;">
                                <ul>
                                    <li style="display: block;text-decoration: unset;">
                                        <img src="{{ asset('front/image/geet.jpg') }}" alt="Ratefy.co" width="100%">
                                    </li>

                                    <li style="display: block;text-decoration: unset" class="reset-contain">
                                           
                                    </li>
                                </ul>
                            </td>
                        </tr>
                    </table>

                    <table align="center" border="0" cellpadding="0" cellspacing="0" width="100%">
                        <thead>
                            <tr>
                                <div class="title title-2 text-center">
                                    <h2 class="title-2" style="font-size: 18px !important; margin-left: 15px; margin-bottom: 15px;">Hello  {{ $name }},</h2>

                                </div>
                                <div class="title title-2">
                                    <p style="font-size: 12px; margin-left: 15px;">
                                        {{ $Body }}
                                    </p>
                                    <p style="font-size: 12px; margin-left: 15px;">
                                        <a href="{{ $url }}" style="padding: 10px 20px !important;  background-color: #00B172; color: #fff; border-radius: 7px !important;">Verify</a>    
                                    </p>    
                                   
                                </div>
                                <div class="title title-2">                               
                                           
                                    <p style="font-size: 12px; margin-left: 15px;">
                                        {{$ComplementaryClosure }}
                                        <br>
                                        {{ $SignatureLine }}
                                    </p>
                                
                                </div>
                                <div class="title title-2">                               
                                            {{ ENV('APP_NAME')}}
                                </div>
                            </tr>
                        </thead>
                    </table>

                    <table class="text-center" align="center" border="0" cellpadding="0" cellspacing="0" width="100%"
                        style="margin-top:40px; background-color: #00B172; ; color: white; padding: 40px 0;">
                        <tr>
                            <td>
                                <table border="0" cellpadding="0" cellspacing="0" class="footer-social-icon"
                                    align="center" class="text-center" style="margin: 8px auto 20px;">
                                    <tr>
                                        <td>
                                            <a href="javascript:void(0)">
                                                <img src="images/fb.png" alt=""
                                                    style="font-size: 25px; margin: 0 18px 0 0;width: 22px;filter: invert(1);">
                                            </a>
                                        </td>
                                        <td>
                                            <a href="javascript:void(0)">
                                                <img src="images/twitter.png" alt=""
                                                    style="font-size: 25px; margin: 0 18px 0 0;width: 22px;filter: invert(1);">
                                            </a>
                                        </td>
                                        <td>
                                            <a href="javascript:void(0)">
                                                <img src="images/insta.png" alt=""
                                                    style="font-size: 25px; margin: 0 18px 0 0;width: 22px;filter: invert(1);">
                                            </a>
                                        </td>
                                        <td>
                                            <a href="javascript:void(0)">
                                                <img src="images/google-plus.png" alt=""
                                                    style="font-size: 25px; width: 22px;filter: invert(1);">
                                            </a>
                                        </td>
                                    </tr>
                                </table>
                                <table border="0" cellpadding="0" cellspacing="0" width="100%">
                                    <tr>
                                        <td>
                                            <h5
                                                style="font-size: 13px; text-transform: uppercase; margin: 0; color:#ddd; letter-spacing:1px;">
                                                Head Quater <span
                                                    style="color: #e22454;">Ratefy</span>.</h5>
                                            <h5
                                                style="font-size: 13px; text-transform: uppercase; margin: 10px 0 0; color:#ddd; letter-spacing:1px;">
                                                 <i class="fas fa-heart"
                                                    style="color: #e22454; margin: 0 5px;"></i> Finetouch Hall, Olubuse Street, Oduduwa Estate, Ile Ife, Osun State, Nigeria</h5>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="width: 100%">
                                            <table class="contact-table">
                                                <tbody style="display: block; width: 100%;">
                                                    <tr
                                                        style="display: block; width: 100%;display: flex; align-items: center; justify-content: center;">
                                                        <td><a href="#"
                                                                style="color: #777; font-weight: 600; text-decoration: underline;">Ratefy.co &copy; @php  echo date('Y')  @endphp</a>
                                                        </td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </tbody>
    </table>

</body>

</html>



<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; margin: 0; padding: 0; background-color: #0C0E0F; color: #ffffff;">
    <table role="presentation" style="width: 100%; max-width: 600px; margin: 20px auto; background-color: #0C0E0F; border-collapse: collapse;">
        <tr>
            <td style="padding: 20px; text-align: center;">
                <img src="{{ asset('/logo/ratefy.png') }}" alt="Ratefy" style="max-width: 150px; height: auto;">
            </td>
        </tr>
        <tr>
            <td style="padding: 30px; background-color: #1a1a1a; border-radius: 10px;">
                <h1 style="margin: 0 0 20px 0; color: #ffffff; font-size: 28px;">Verify your account</h1>
                <p style="margin-bottom: 15px;">Dear {{ $name }},</p>
                <p style="margin-bottom: 15px;">{{ $body }}</p>
                <p style="text-align: left; margin-bottom: 20px;">
                    <a href="#" style="display: inline-block; padding: 10px 20px; background-color: #00ff99; color: #000000; text-decoration: none; border-radius: 5px; font-weight: bold;">Verify account</a>
                </p>
                <p style="margin-bottom: 15px;">If you did not create an account with Ratefy, please ignore this email. If you need any assistance, please contact our support team on WhatsApp.</p>
                <p style="margin-bottom: 5px;"> {{$ComplementaryClosure }},</p>
                <p style="margin-top: 0;">{{ $SignatureLine }}</p>
            </td>
        </tr>
        <tr>
          <td style="padding: 20px; text-align: center; font-size: 12px; color: #666666;">
              <p style="margin-bottom: 15px;">RATEFY TECHNOLOGY LTD</p>
              <div>
                  <!-- Email -->
                  <a href="#" style="display: inline-block; margin: 0 5px; width: 40px; height: 40px; border-radius: 50%; background-color: #00ff99;">
                      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="#000000" width="24px" height="24px" style="margin-top: 8px;">
                          <path d="M0 0h24v24H0z" fill="none"/>
                          <path d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/>
                      </svg>
                  </a>
                  
                  <!-- Twitter/X -->
                  <a href="#" style="display: inline-block; margin: 0 5px; width: 40px; height: 40px; border-radius: 50%; background-color: #00ff99;">
                      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="#000000" width="24px" height="24px" style="margin-top: 8px;">
                          <path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/>
                      </svg>
                  </a>
                  
                  <!-- Instagram -->
                  <a href="#" style="display: inline-block; margin: 0 5px; width: 40px; height: 40px; border-radius: 50%; background-color: #00ff99;">
                      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="#000000" width="24px" height="24px" style="margin-top: 8px;">
                          <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/>
                      </svg>
                  </a>
                  
                  <!-- TikTok -->
                  <a href="#" style="display: inline-block; margin: 0 5px; width: 40px; height: 40px; border-radius: 50%; background-color: #00ff99;">
                      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="#000000" width="24px" height="24px" style="margin-top: 8px;">
                          <path d="M12.525.02c1.31-.02 2.61-.01 3.91-.02.08 1.53.63 3.09 1.75 4.17 1.12 1.11 2.7 1.62 4.24 1.79v4.03c-1.44-.05-2.89-.35-4.2-.97-.57-.26-1.1-.59-1.62-.93-.01 2.92.01 5.84-.02 8.75-.08 1.4-.54 2.79-1.35 3.94-1.31 1.92-3.58 3.17-5.91 3.21-1.43.08-2.86-.31-4.08-1.03-2.02-1.19-3.44-3.37-3.65-5.71-.02-.5-.03-1-.01-1.49.18-1.9 1.12-3.72 2.58-4.96 1.66-1.44 3.98-2.13 6.15-1.72.02 1.48-.04 2.96-.04 4.44-.99-.32-2.15-.23-3.02.37-.63.41-1.11 1.04-1.36 1.75-.21.51-.15 1.07-.14 1.61.24 1.64 1.82 3.02 3.5 2.87 1.12-.01 2.19-.66 2.77-1.61.19-.33.4-.67.41-1.06.1-1.79.06-3.57.07-5.36.01-4.03-.01-8.05.02-12.07z"/>
                      </svg>
                  </a>
                  
                  <!-- LinkedIn -->
                  <a href="#" style="display: inline-block; margin: 0 5px; width: 40px; height: 40px; border-radius: 50%; background-color: #00ff99;">
                      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="#000000" width="24px" height="24px" style="margin-top: 8px;">
                          <path d="M19 0h-14c-2.761 0-5 2.239-5 5v14c0 2.761 2.239 5 5 5h14c2.762 0 5-2.239 5-5v-14c0-2.761-2.238-5-5-5zm-11 19h-3v-11h3v11zm-1.5-12.268c-.966 0-1.75-.79-1.75-1.764s.784-1.764 1.75-1.764 1.75.79 1.75 1.764-.783 1.764-1.75 1.764zm13.5 12.268h-3v-5.604c0-3.368-4-3.113-4 0v5.604h-3v-11h3v1.765c1.396-2.586 7-2.777 7 2.476v6.759z"/>
                      </svg>
                  </a>
              </div>
              <p style="margin-top: 15px;">
                  <a href="#" style="color: #666666; text-decoration: underline;">unsubscribe</a>
              </p>
          </td>
      </tr>
  </table>
    </table>
</body>
</html>




<tr>
<td>
<table class="footer" align="center" width="570" cellpadding="0" cellspacing="0" role="presentation">
<tr>
<td class="content-cell" align="center">
{{ Illuminate\Mail\Markdown::parse($slot) }}
</td>
</tr>
</table>
</td>
</tr>



@props(['url'])
<tr>
<td class="header">
<a href="{{ $url }}" style="display: inline-block;">
@if (trim($slot) === 'Laravel')
<img src="https://laravel.com/img/notification-logo.png" class="logo" alt="Laravel Logo">
@else
{{ $slot }}
@endif
</a>
</td>
</tr>




@component('mail::layout')

@slot ('header')
@component('mail::header', ['url' => config('app.url')])
    
@endcomponent
@endslot

@component('mail::message')

@component('mail::panel')
<h1 style="margin: 0 0 20px 0; color: #ffffff; font-size: 28px;"> Verify your account </h1>
@endcomponent
<p style="margin-bottom: 15px;">Dear {{ $name }},</p>

<p style="margin-bottom: 15px;">Thank you for registering with Ratefy! To complete your registration and activate your account, please verify your email address by clicking the link below:</p>


@component('mail::button', ['url' => $url ])
Verify no account
@endcomponent
<p style="margin-bottom: 15px;">If you did not create an account with Ratefy, please ignore this email. If you need any assistance, please contact our <a href="https://whatsapp.com/link/links">support team on WhatsApp.</a></p>

<a style="color: #cdcdcb; font-weight: bolder; text-decoration: none; letter-spacing: 2px;" href="mailto:chat@ratefy.co">contact us immediately</a>

@component('mail::table')
| Laravel       | Table         | Example       |
| ------------- | :-----------: | ------------: |
| Col 2 is      | Centered      | $10           |
| Col 3 is      | Right-Aligned | $20           |

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