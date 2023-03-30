@extends('layouts.default')
@section('content')

                 				
<tr>
    <td>
        <table width="100%">
            <tr>
                <td style="text-align:center; padding:30px 0px;">
					<img src="{{URL::asset('assets/images/OTP-img.png') }}" alt="otp" />
				</td>
            </tr>
        </table>
    </td>            
</tr>
<tr>
    <td>
        <table style="width:100%; background:#fff; padding:0px 30px;" cellpadding="0">
			<tr>
				<td style="padding:30px 0px 20px; font-weight:600;">Dear Administrator,</td>
			</tr>
			<tr>
				<td style=" font-weight:400;">Someone trying to access this url <span style=" font-weight:800;">{{$url}}</span> which is not exist in the system.
				</td>
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