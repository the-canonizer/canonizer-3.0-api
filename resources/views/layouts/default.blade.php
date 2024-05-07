<!DOCTYPE html>
<html>
	<head>
        @include('layouts.head') 
	</head>
	<body style="font-family: 'Ubuntu', sans-serif;background-color: #F8F8F8; background-image:  url('{{url('assets/images/email-bg.png')}}'); background-repeat: no-repeat; ">
		<center class="wrapper" style="background-color: transparent !important;">
        	@include('layouts.header')    
			<table class="main" style="width:100%; background-color: #F8F8F8; border-radius: 15px 15px 0px 0px; margin-top: 30px;">
				<tbody>    
					@yield('content')
				</tbody>
              
                <tfoot>                  
					@include('layouts.footer')              
                </tfoot>              
			</table>
		</center>
	</body>
</html>