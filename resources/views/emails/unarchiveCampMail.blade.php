@extends('layouts.default')
@section('content')
    <tr>
        <td>
            <table width="100%">
                <tr>
                    <td style="text-align:center; padding:30px 0px;"><img
                            src="{{ URL::asset('assets/images/OTP-img.png') }}"></td>
                </tr>
            </table>
        </td>
    </tr>
    <tr>
        <td>
            <table width="100%" style=" background:#fff; padding:0px 30px;" cellpadding="0">
                <tr>
                    <td style="padding:30px 0px 20px; font-weight:600;">Hello {{ $user->first_name }}
                        {{ $user->last_name }}, </td>
                </tr>
                <tr>
                    <td style="padding:10px 0px 10px;">
                        <p>
                             <a href="{{ \App\Facades\Util::linkForEmail($data['link']) }}">{{ $data['object'] }} </a>
                              has been unarchived.
                        </p>
                        <p>                          
                            <h4 style="margin-bottom: 15px;">You are receiving this e-mail because:</h4>
                            <ul style="margin-left: 45px;">
                                You were directly supporting this camp  <a href="{{ \App\Facades\Util::linkForEmail($data['link']) }}">{{ $data['object'] }} </a></li>
                            </ul>

                            <h4 style="margin-top: 15px;">Note:</h4>
                            <p style="margin-top: 15px;">
                                We request that all <b>direct</b> supporters of a camp continue to receive notifications
                                and take responsibility for the camp. If you <b>delegate</b> your support to someone
                                else, you will no longer receive these notifications. <b>Delegating</b> your support to
                                someone else will also result in your support following them for all camps in this
                                topic.
                            </p>
                        </p>
                    </td>
                </tr>


                <tr>
                    <td style="padding-top:10px;  font-weight:400; ">Sincerely,</td>
                </tr>
                <tr>
                    <td style="padding-top:10px; font-weight:400; padding-bottom: 20px;color:#497BDF;">The Canonizer Team
                    </td>
                </tr>
            </table>
        </td>
    </tr>
@stop
