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
                            {{-- TODO: Commented Code from Issue [#665](https://github.com/the-canonizer/canonizer-3.0-api/issues/665) --}}
                            {{-- <a target="_blank"
                                href="<?= \App\Facades\Util::linkForEmail(config('global.APP_URL_FRONT_END') .'/user/supports/'. $data['nick_name_id'] . '?topicnum=&campnum=&canon=' . $data['namespace_id']) ?>">{{ $data['nick_name'] }}</a>
                            has proposed a change to this {{ $data['type'] }} <a
                                href="{{ \App\Facades\Util::linkForEmail($link) }}">{{ $data['object'] }} </a>
                            @if (empty($data['is_live']) || $data['is_live'] != 1)
                                which you currently
                                {{ isset($data['subscriber']) && $data['subscriber'] == 1 ? 'subscribed' : 'directly support' }}.
                                If no supporters of this {{ $data['type'] }}
                                <a href="{{ \App\Facades\Util::linkForEmail($link) }}">{{ $data['object'] }} </a> object to this change,
                                it will go live in one day / 24 hours
                                <br>
                            @endif --}}

                            <a target="_blank"
                                href="<?= \App\Facades\Util::linkForEmail(config('global.APP_URL_FRONT_END') .'/user/supports/'. $data['nick_name_id'] . '?topicnum=&campnum=&canon=' . $data['namespace_id']) ?>">{{ $data['nick_name'] }}</a>
                            has <a href="{{ \App\Facades\Util::linkForEmail($link) }}">proposed a change</a> to this {{ $data['type'] }} <b>{!! $data['object'] !!}</b>
                            @if (empty($data['is_live']) || $data['is_live'] != 1)
                                which you currently
                                {{ isset($data['subscriber']) && $data['subscriber'] == 1 ? 'subscribed' : 'directly support' }}.
                                If no supporters of this {{ $data['type'] }}
                                {!! $data['object'] !!} object to this change,
                                it will go live in one day / 24 hours
                                <br>
                            @endif

                            
                            @if (!empty($data['note']))
                                <p>Edit summary : {{ $data['note'] }}</p>
                            @endif
                        </p>
                        <p>
                            @if (isset($data['subscriber']) && $data['subscriber'] == 1)
                                <h4 style="margin-bottom: 15px;">You are receiving this e-mail because:</h4>
                                <ul style="margin-left: 45px;">
                                    @if (isset($data['support_list']) && count($data['support_list']) > 0)
                                        @foreach ($data['support_list'] as $support)
                                            <li>You are subscribed to <b>{!!  \App\Facades\Util::linkForEmail($support) !!}</b></li>
                                        @endforeach
                                    @else
                                        <li>You are subscribed to <b><a href="{{ \App\Facades\Util::linkForEmail(config('global.APP_URL_FRONT_END') . '/' . $data['camp_url']) }}">
                                                {{ $data['camp_name'] }} </a></b></li>
                                    @endif

                                </ul>
                            @else
                                <h4 style="margin-bottom: 15px;">You are receiving this e-mail because:</h4>
                                <ul style="margin-left: 45px;">
                                    @if (isset($data['support_list']) && count($data['support_list']) > 0)
                                        @foreach ($data['support_list'] as $support)
                                            <li>You are directly supporting <b>{!!  \App\Facades\Util::linkForEmail($support) !!}</b></li>
                                        @endforeach
                                    @else
                                        <li>You are directly supporting <b><a
                                                href="{{ \App\Facades\Util::linkForEmail(config('global.APP_URL_FRONT_END') . '/' . $data['camp_url']) }}">
                                                {{ $data['camp_name'] }} </a></b></li>
                                    @endif

                                    @if (isset($data['also_subscriber']) &&
                                        $data['also_subscriber'] == 1 &&
                                        isset($data['sub_support_list']) &&
                                        count($data['sub_support_list']) > 0)
                                        @foreach ($data['sub_support_list'] as $support)
                                            <li>You are subscribed to <b>{!!  \App\Facades\Util::linkForEmail($support) !!}</b></li>
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
