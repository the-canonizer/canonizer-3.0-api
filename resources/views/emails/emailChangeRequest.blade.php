@extends('layouts.default')
@section('content')

                 				
<tr>
    <td>
        <table width="100%">
            <tr>
                <td style="text-align:center; padding:30px 0px;"><img src="{{URL::asset('assets/images/OTP-img.png') }}" alt="otp"></td>
            </tr>
        </table>
    </td>            
</tr>
<tr>
    <td>
        <table width="100%" style=" background:#fff; padding:0px 30px;" cellpadding="0">
            <tr>
                    <td style="padding:30px 0px 20px; font-weight:600;">Hello {{ $user->first_name }} {{ $user->last_name}},</td>
            </tr>
            <tr>
                <td style=" font-weight:400;">
                    We've received a request to update the email address associated with your account. As part of our security measures, we require a one-time password (OTP) to authenticate this change.
                </td>
            </tr>
            <tr>
                <td style="padding-top:10px;  font-weight:400;">Please find your OTP below:</td>
                </tr>
                <tr>
                <td style="padding-top:10px; padding-bottom: 10px; font-weight:400; font-size:30px; color:#497BDF;">{{ $user->otp}}</td>
            </tr>
            <tr>
                <td style="padding-top:10px;  font-weight:400; ">
                    To proceed with the email change, please enter this OTP in the designated field on our website. This ensures that only you, the account owner, can make this adjustment.
                </td>
            </tr>
            <tr>
                <td style="padding-top:10px; font-weight:400; padding-bottom: 20px; ">If you didn't initiate this request, please contact our support team immediately at email: <a href="mailto:support@canonizer.com" style="color:#497BDF; font-weight: 600; text-decoration:none;">support@canonizer.com</a></td>
            </tr>
            <tr>
                <td style="padding-top:10px;  font-weight:400; ">Sincerely,</td>
            </tr>
            <tr>
                <td style="padding-top:10px; font-weight:400; padding-bottom: 20px;color:#497BDF;">The Canonizer Team </td>
            </tr>
        </table>
    </td>
</tr>    
@stop