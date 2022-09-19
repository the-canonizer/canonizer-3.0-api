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
                        <p style="margin-bottom: 15px;">
                            <a target="_blank" href="{{ $nickname_url }}">{{ $data['nick_name']->nick_name }}</a> {{ $data['post_type'] }}
                            the following post to the Camp
                            <a href="{{ $data['camp_url'] }}"> {{ $data['camp_name'] }} </a> forum:

                        </p>

                        <h4 style="margin-bottom: 15px;">Thread Title: </h4>
                        <p style="margin-bottom: 15px;"> <a href="{{ $link }}">{{ $data['thread'][0]->title }}</a> </p>

                        <h4 style="margin-bottom: 15px;">Post: </h4>

                        <p style="margin-bottom: 15px;"> {!! $data['post'] !!}. </p>

                        <p>
                            @if (isset($data['subscriber']) && $data['subscriber'] == 1)
                                <h4 style="margin-bottom: 15px;">You are receiving this e-mail because:</h4>
                                <ul style="margin-left: 45px;">
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
                                <h4 style="margin-bottom: 15px;">You are receiving this e-mail because:</h4>
                                <ul style="margin-left: 45px;">
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

                                <h4 style="margin-top: 15px;">Note:</h4>
                                <p style="margin-top: 15px;">
                                    We request that all <b>direct</b> supporters of a camp continue to receive notifications
                                    and take responsibility for the camp. If you <b>delegate</b> your support to someone
                                    else, you will no longer receive these notifications. <b>Delegating</b> your support to
                                    someone else will also result in your support following them for all camps in this
                                    topic.
                                </p>
                            @endif
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
