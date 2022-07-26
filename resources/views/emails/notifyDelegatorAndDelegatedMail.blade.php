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
                        <p>
                            <?php if(isset($data['notify_delegated_user']) && $data['notify_delegated_user']){ ?>
                                <a target="_blank" href="/user/supports/{{ $data['nick_name_id'] }}?topicnum=&campnum=&namespace=' . $data['namespace_id']; ?>">{{ $data['nick_name']}}</a>
                                    has delegated their support to you in this topic: <a target="_blank" href="{{ $data['camp_link'] }}">{{ $data['object'] }}</a></b>
                            <?php } else{ ?>
                                You have delegated your support to <a target="_blank"
                                href="/user/supports/{{ $data['delegated_nick_name_id'] }}?topicnum=&campnum=&namespace=' . $data['namespace_id']; ?>">{{ $data['delegated_nick_name']}}</a> in this topic: <a href="{{ $data['camp_link'] }}"><b>{{ $data['object']}}</b></a>
                            <?php } ?>
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
