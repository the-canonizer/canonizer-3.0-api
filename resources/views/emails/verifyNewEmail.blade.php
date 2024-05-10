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
                Thank you for updating your email address with us. To complete the process, please verify your new email address by entering the verification code sent to you.
                </td>
            </tr>
            <tr>
                <td style="padding-top:10px;  font-weight:400;">Verification Code(OTP):</td>
                </tr>
                <tr>
                <td style="padding-top:10px; padding-bottom: 10px; font-weight:400; font-size:30px; color:#497BDF;">{{ $user->otp}}</td>
            </tr>
            <tr>
                <td style="padding-top:10px;  font-weight:400; ">
                Once you've entered the code, your new email address will be confirmed, and you'll be able to recieve all email notification from our website on this email address only.
                </td>
            </tr>
            <tr>
                <td style="padding-top:10px; font-weight:400; padding-bottom: 20px; ">If you encounter any issues or did not request this change, please contact our support team immediately at email: <a href="mailto:support@canonizer.com" style="color:#497BDF; font-weight: 600; text-decoration:none;">support@canonizer.com</a></td>
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