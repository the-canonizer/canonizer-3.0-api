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
                    <td style="padding:30px 0px 20px; font-weight:600;">Dear user,</td>
            </tr>
            <tr>
              <td>You delegated your support to <a target="_blank" href="{{ $data['nick_name_link'] }}"><?= $data['promotedFrom']->nick_name; ?></a> who delegated their support to <a target="_blank" href="{{ $data['delegated_nick_name_link'] }}"><?= $data['promotedTo']->nick_name; ?></a> in <a href="<?= $data['camp_link']; ?>" target="_blank"><?= $data['camp']->camp_name; ?></a> camp in <a href="<?= $data['topic_link']; ?>" target="_blank"><?= $data['topic']->topic_name; ?></a> topic.</td>
            </tr>
            
            <tr>
               <td>They have stopped delegating their support in this topic so your support has been delegated to <a target="_blank" href="{{ $data['delegated_nick_name_link'] }}"><?= $data['promotedTo']->nick_name; ?></a>.</td>
            </tr>

            <tr>
                <td><b>No other action is required.</b></td>
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