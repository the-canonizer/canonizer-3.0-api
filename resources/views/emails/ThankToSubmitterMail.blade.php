@extends('layouts.default')
@section('content')

                 				
<tr>
    <td>
        <table width="100%">
            <tr>
                <td style="text-align:center; padding:30px 0px;"><img src="{{ URL::asset('assets/images/OTP-img.png') }}"></td>
            </tr>
        </table>
    </td>            
</tr>
<tr>
    <td>
        <table width="100%" style=" background:#fff; padding:0px 30px;" cellpadding="0">
            <tr>
                    <td style="padding:30px 0px 20px; font-weight:600;">Hello , {{ $user->first_name }}
                        {{ $user->last_name }} </td>
            </tr>
           
            <tr>
                <td style="padding-top: 60px;  font-weight:400;">  <p>You proposed a change for {{ $data->type }} : <b>{{ $data->object }}</b></p></td>
                </tr>
                <tr>
                <td style="padding-top:10px; padding-bottom: 60px; font-weight:400; font-size:20px; color:#497BDF;">@component('mail::button', ['url' => $data->link])Click Here To View @endcomponent</td>
            </tr>
            <tr>
                <td style="padding-top:10px;  font-weight:400; ">  <p>Thank you for your submittal to Canonizer.com.</p></td></td>


            </tr>
            <tr>
                <td style="padding-top:10px; font-weight:400; padding-bottom: 20px; ">@component('mail::button', ['url' => $data->historylink])Click Here To View History @endcomponent</td>
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