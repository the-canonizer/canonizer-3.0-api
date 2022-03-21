@extends('layouts.default')
@section('content')

                 				
<tr>
    <td>
        <table width="100%">
            <tr>
                <td style="text-align:center; padding:30px 0px;"><img src="{{URL::asset('assets/images/OTP-img.png') }}"></td>
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
                <?php if(!$settingFlag){ ?>
                <td style=" font-weight:400;">Thank you for registering an account with  canonizer.com</td>
                <?php } ?>
            </tr>
            <tr>
                <td style="padding-top: 20px;  font-weight:400;">Your one time verification code is</td>
                </tr>
                <tr>
                <td style="padding-top:10px; padding-bottom: 20px; font-weight:400; font-size:30px; color:#497BDF;">{{ $user->otp}}</td>
            </tr>
            <tr>
                <td style="padding-top:10px;  font-weight:400; ">If you ever have any issues or feedback,</td>


            </tr>
            <tr>
                <td style="padding-top:10px; font-weight:400; padding-bottom: 20px; ">Feel free to email: <a href="mailto:support@canonizer.com" style="color:#497BDF; font-weight: 600; text-decoration:none;">support@canonizer.com</a></td>
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