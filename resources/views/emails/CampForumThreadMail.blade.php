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
                    <td style="padding-top: 0px;  font-weight:400;">
                        <p> <a target="_blank"
                                href="{{ $nickname_url }}">{{ $data['nick_name']->nick_name }}</a>
                            has created the new thread
                            <a href="{{ $link }}">{{ $data['thread_title'] }}</a></b>
                        </p> 
                    </td>
                </tr>
                <tr>
                    <td style="padding-top:10px; padding-bottom: 30px; font-weight:400; font-size:20px;">
                        <p>Camp Name: <a style="color:#497BDF;" href="{{ $data['camp_url'] }}"> {{ $data['camp_name'] }} </a>
                        </p>
                    </td>
                </tr>
                <tr>
                    <td style="padding-top: 0px;  font-weight:400;">
                        @if (isset($data['subscriber']) && $data['subscriber'] == 1)
                            <h4>You are receiving this e-mail because:</h4>

                            <ul style="padding-left: 50px;">
                                @if (isset($data['support_list']) && count($data['support_list']) > 0)
                                    @foreach ($data['support_list'] as $support)
                                        <li>You are subscribed to {!! $support !!}</li>
                                    @endforeach
                                @else
                                    <li>You are subscribed to <a href="{{ $data['camp_url'] }}">
                                            {{ $data['camp_name'] }} </a></li>
                                @endif
                            </ul>
                        @else
                            <h4 style="margin-bottom: 20px">You are receiving this e-mail because:</h4>

                            <ul style="padding-left: 50px;">
                                @if (isset($data['support_list']) && count($data['support_list']) > 0)
                                    @foreach ($data['support_list'] as $support)
                                        <li>You are directly supporting {!! $support !!}</li>
                                    @endforeach
                                @else
                                    <li>You are directly supporting <a href="{{ $data['camp_url'] }}">
                                            {{ $data['camp_name'] }} </a></li>
                                @endif

                                @if (isset($data['also_subscriber']) && $data['also_subscriber'] == 1 && isset($data['sub_support_list']) && count($data['sub_support_list']) > 0)
                                    @foreach ($data['sub_support_list'] as $support)
                                        <li>You are subscribed to {!! $support !!}</li>
                                    @endforeach
                                @endif
                            </ul>

                            <h4 style="margin-top:20px;margin-bottom:20px">Note:</h4>
                            <p>
                                We request that all <b>direct</b> supporters of a camp continue to receive notifications
                                and
                                take responsibility for the camp. If you <b>delegate</b> your support to someone else,
                                you
                                will no longer receive these notifications. <b>Delegating</b> your support to someone
                                else
                                will also result in your support following them for all camps in this topic.
                            </p>
                        @endif
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
