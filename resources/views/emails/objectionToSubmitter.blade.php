@extends('layouts.default')
@section('content')
    <tr>
        <td>
            <table width="100%">
                <tr>
                    <td style="text-align:center; padding:30px 0px;">
                        <img src="{{ URL::asset('assets/images/OTP-img.png') }}">
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
                    <td style="padding-top: 10px;  font-weight:400;">
                        {{-- TODO: Commented Code from Issue [#665](https://github.com/the-canonizer/canonizer-3.0-api/issues/665) --}}
                        {{-- <p><a target="_blank"
                                href="<?=  \App\Facades\Util::linkForEmail(config('global.APP_URL_FRONT_END') .'/user/supports/'. $data['nick_name_id'] .  '?topicnum=&campnum=&canon=' . $data['namespace_id']) ?>">{{ $data['nick_name'] }}</a>
                            has objected to your <a href="{{ \App\Facades\Util::linkForEmail($data['history_link']) }}" target='_balnk'>proposed
                                change</a> submitted for {{ $data['type'] }} (<a
                                href="{{ \App\Facades\Util::linkForEmail($data['topic_link']) }}">{{ $data['object'] }}</a>) {{ $data['object_type'] }} </p> --}}

                        <p>
                            <a target="_blank" href="<?=  \App\Facades\Util::linkForEmail(config('global.APP_URL_FRONT_END') .'/user/supports/'. $data['nick_name_id'] .  '?canon=' . $data['namespace_id']) ?>">{{ $data['nick_name'] }}</a>
                            has objected to your <a href="{{ \App\Facades\Util::linkForEmail($data['history_link']) }}" target='_blank'>proposed change</a> submitted for {{ $data['type'] }} 
                            ({!! $data['object'] !!}) {{ $data['object_type'] }} </p>
                    </td>
                </tr>
                <tr>
                    <td style="padding-top:10px; padding-bottom: 10px; font-weight:300; font-size:17px; color:#497BDF;">
                        @component('mail::button', ['url' =>  \App\Facades\Util::linkForEmail($data['help_link']) ])
                            See this link for options you can take when there are objections
                        @endcomponent
                    </td>
                </tr>
        </td>
    </tr>
    <tr>
        <td style="padding-top:10px;  font-weight:400; ">
            Sincerely,
        </td>
    </tr>
    <tr>
        <td style="padding-top:10px; font-weight:400; padding-bottom: 20px;color:#497BDF;">
            The Canonizer Team
        </td>
    </tr>
    </table>
    </td>
    </tr>
@stop
