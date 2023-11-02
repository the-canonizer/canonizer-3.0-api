@extends('layouts.default')
@section('content')
    <tr>
        <td>
            <table width="100%">
                <tr>
                    <td style="text-align:center; padding:30px 0px;">
                        <img src="{{ URL::asset('assets/images/OTP-img.png') }}" alt="otp" />
                    </td>
                </tr>
            </table>
        </td>
    </tr>
    <tr>
        <td>
            <table width="100%" style=" background:#fff; padding:0px 30px;" cellpadding="0">
                <tr>
                    <td style="padding:30px 0px 20px; font-weight:600;">
                        Hello {{ $user->first_name }} {{ $user->last_name }},
                    </td>
                </tr>

                <tr>
                    <td style="font-weight:400;">
                        {{-- TODO: Commented Code from Issue [#665](https://github.com/the-canonizer/canonizer-3.0-api/issues/665) --}}
                        <p>You proposed a change for {{ $data->type }} : <b>{!! $data->object !!}</b></p>
                    </td>
                </tr>
                <tr>
                    <td style="font-weight:400;">
                        <br>
                        {{-- TODO: Commented Code from Issue [#665](https://github.com/the-canonizer/canonizer-3.0-api/issues/665) --}}
                        <p><b>{{ ucfirst($data->type) }} Summary:</b> {{ $data->note }}</p>
                    </td>
                </tr>
                <tr>
                    <td style="font-weight:400; color:#497BDF;">
                        @component('mail::button', ['url' => \App\Facades\Util::linkForEmail($data->link)])
                            Click Here To View
                        @endcomponent
                    </td>
                </tr>
                <tr>
                    <td style="font-weight:400; ">
                        <p>Thank you for your submittal to Canonizer.com.</p>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
    <tr>
        <td style="font-weight:400;background:#fff;  color:#497BDF;">
            @component('mail::button', ['url' => \App\Facades\Util::linkForEmail($data->historylink)])
                Click Here To View History
            @endcomponent
        </td>
    </tr>
    <tr>
        <td>
            <table width="100%" style=" background:#fff; padding:0px 30px;" cellpadding="0">
            <tr>
                <td style="padding-top:10px;  font-weight:400; ">
                    Sincerely,
                </td>
            </tr>
            </table>
        </td>
    </tr>
    <tr>
    <td>
    <table width="100%" style=" background:#fff; padding:0px 30px;" cellpadding="0">
       <tr>
       <td style="padding-top:10px; font-weight:400; padding-bottom: 20px;color:#497BDF;">
            The Canonizer Team
        </td>
       </tr>
    </table>
    </td>
    </tr>
@stop