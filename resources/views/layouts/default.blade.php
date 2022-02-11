<!DOCTYPE html>
<html>
	<head>
		@include('layouts.head')
	</head>
	<body style="font-family: 'Ubuntu', sans-serif;">
		<center class="wrapper">
			@include('layouts.header')
			<table class="main" style="width:100%; background:#F8F8F8; border-radius: 15px 15px 0px 0px;     margin-top: 50px;">
				<tbody>

				@yield('content')

				<footer>
						@include('layouts.footer')
				</footer>
				
				</tbody>
			</table>
		</center>
	</body>
</html>