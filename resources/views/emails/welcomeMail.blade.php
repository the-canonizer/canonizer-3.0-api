<!DOCTYPE html>
<html>
   <head>
      <meta charset="utf-8">
      <meta name="viewport" content="width=device-width, initial-scale=1">
      <title>Email Template</title>
      <link rel="preconnect" href="https://fonts.googleapis.com">
      <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
      <link href="https://fonts.googleapis.com/css2?family=Ubuntu:wght@400;500&display=swap" rel="stylesheet">
      <style>
         *{margin:0; padding:0;}
         body{
		margin: 0;
	}
         table{
         border-spacing: 0;
         }
         td{
         padding: 0;
         }
         img{
         border: 0;
         }
         .wrapper{
         width: 100%;
         table-layout: fixed;
         background:url(images/email-bg.jpg);
         background-repeat: no-repeat;
          
         padding-bottom: 40px;
          padding-top: 40px;
         }

         .main{
         width:100%;
         max-width: 600px;
         border-spacing: 0; 
         background:#fff; 
         border-radius: 15px 15px 0px 0px;     
         margin-top: 50px;
        
         }
         .two-column{
         text-align: center;
         font-size: 0;
         }
         .two-column .column{
         width: 100%;
         max-width: 300px;
         display: inline-block;
         
         }
          .two-column .column-2 tbody{ float:right; }
         button{
           background:#F89D15; 
           color:#fff; 
           border:none; 
           padding: 13.5px 43px; 
           border-radius:3px;
           cursor: pointer;
         } 
       @media(max-width:550px){
         	 .two-column .column-2 tbody { float:unset;  }
         	 .two-column .column{display: table; margin: auto;}
         	 .two-column .column-2 {padding-top:15px;}
         }
      </style>
   </head>
   <body style="font-family: 'Ubuntu', sans-serif;">
      <center class="wrapper">
         
            <table width="100%"  cellpadding="0" cellspacing="0">
	  		<tbody>
	  		 	 <tr>
	  		 	 	<td style="text-align: center;"><img src="{{URL::asset('assets/images/logo.png') }}" style="margin:auto;"></td>
	  		 	 </tr>
	  		 	</tbody>
	  	</table>
            <table class="main">
               <tbody>
                  <tr>
                  	<td>
                  		 <table width="100%" style=" padding:15px 0px 15px;" cellpadding="0" cellspacing="0">
                  		 	<tr>
                  		 		 <td style="text-align:center; padding:10px 0px 0px; font-size:24px; font-weight:600;">Welcome to Canonizer</td>
                  		 	</tr>
                  		 </table>
                  	</td>
                     
                  </tr>
                  <tr>
                  	<td>
                  		 <table width="100%" style=" padding: 0px 30px;">
                  		 	<tr>
                  		 		 <td style="text-align:center; padding:10px 0px 0px; font-size:24px; font-weight:600;">
                        <a href="https://player.vimeo.com/video/307590745"><img src="{{URL::asset('assets/images/email-video-img.jpg') }}" style="max-width:100%;"></a>
                     </td>
                  		 	</tr>
                  		 </table>
                  	</td>
                     
                  </tr>
                  <tr>
                  	 <td>
                  	 	<table style="width:100%; background:#fff; padding:0px 30px;" cellpadding="0">
	              
	                  <tr>
	                     <td style="padding:30px 0px 20px; font-weight:600;">Hello {{ $user->first_name}} {{ $user->last_name}}</td>
	                  </tr>
	                  <tr>
	                     <td style=" font-weight:400;">Thank You For registering an account with  canonizer.com</td>
	                  </tr>
	                  <tr>
	                     <td style="padding-top: 60px;  font-weight:400;">Here is a link to a help index page:</td>
	                  </tr>
	                  <tr>
	                     <td style="padding-top:10px; padding-bottom: 60px; font-weight:400; font-size:30px; color:#497BDF;"><a href="#"><button> Click here</button></a></td>
	                  </tr>
	                  <tr>
	                     <td style="padding-top:10px;  font-weight:400; ">if you ever have any issues or feedback,</td>
	                  </tr>
	                  <tr>
	                     <td style="padding-top:10px; font-weight:400; padding-bottom: 20px; ">feel free to email: <a href="mailto:support@caronizer.com" style="color:#497BDF; font-weight: 600; text-decoration:none;">support@caronizer.com</a></td>
	                  </tr>
	                  <tr>
	                     <td style="padding-top:10px;  font-weight:400; ">Sincerely,</td>
	                  </tr>
	                  <tr>
	                     <td style="padding-top:10px; font-weight:400; padding-bottom: 20px;color:#497BDF;">The caronizer Team </td>
	                  </tr>
	               
	            </table>
                  	 </td>
                  </tr>
	            <tr>
	            	 <td>
	            	 	<table width="100%" align="center"  style=" background:#EAEDF2; border-radius: 0px 0px  15px 15px;   ">
	               
	                  <tr>
	                     <td style="padding:30px 0 0px;">	
	                        <table width="100%" >
	                           <tr>
	                              <td class="two-column">
	                                 <table class="column">
	                                    <tr>
	                                       <td style="padding:0 30px 0px;"> 
	                                          <a href="https://www.canonizer.com/" target="_blank"><img src="{{URL::asset('assets/images/logo-grey.png') }}" width="137" title="logo"></a>
	                                       </td>
	                                    </tr>
	                                 </table>
	                                 <table class="column column-2">
	                                    <tr >
                                            <td style="padding:0px 30px; " > 
                                                <a href="javascript:void(0);" style=" font-size:14px; text-decoration:none;     color: #20395A;     line-height: 30px;     vertical-align: bottom;">Follow Us</a>
                                               <a href="#"><img src="{{URL::asset('assets/images/instagram.png') }}" width="28px;" 
                                                   style="padding-left:10px;"></a>
                                               <a href="#"><img src="{{URL::asset('assets/images/facebook.png') }}" width="28px;" style="padding-left:5px;"></a>
                                               <a href="#"><img src="{{URL::asset('assets/images/twitter.png') }}" width="28px;" style="padding-left:5px;"></a>
                                               <a href="#"><img src="{{URL::asset('assets/images/youtube.png') }}" width="28px;" style="padding-left:5px;"></a>
                                               <a href="#"><img src="{{URL::asset('assets/images/linkedin.png') }}" width="28px;" style="padding-left:5px;"></a>
                                            </td>
	                                    </tr>
	                                 </table>
	                              </td>
	                           </tr>
	                        </table>
	                     </td>
	                  </tr>
	                  <tr>
	                     <td>
	                        <table width="100%" style="padding:0px 30px;">
	                           <tr>
	                              <td  style="padding-top:30px;  text-align: center;   padding-bottom: 20px;"> 
	                                 <a href="#" style="color:#20395A; text-decoration: none; font-size:14px;"> Services </a>
	                                 <a href="https://www.canonizer.com/topic/132-Help/1-Agreement" target="_blank" style="color:#20395A; text-decoration: none; padding-left:10px; font-size:14px;"> Help </a>
	                                 <a href="https://www.canonizer.com/files/2012_amplifying_final.pdf" target="_blank" style="color:#20395A; text-decoration: none; padding-left:10px; font-size:14px;"> White Paper </a>
	                                 <a href="https://www.canonizer.com/blog/" target="_blank" style="color:#20395A; text-decoration: none; padding-left:10px; font-size:14px;"> Blog </a>
	                                 <a href="https://www.canonizer.com/topic/6-Canonizer-Jobs/1-Agreement" target="_blank" style="color:#20395A; text-decoration: none; padding-left:10px; font-size:14px;"> Jobs </a>
	                              </td>
	                           </tr>
	                        </table>
	                     </td>
	                  </tr>
	                  <tr>
	                     <td>
	                        <table width="100%" style="padding:0px 30px;">
	                           <tr>
	                              <td style="padding-top:20px; text-align: center; border-top:solid 1px #BDC1C8; font-size:12px; color: #76787c;">
	                                 Copyright owned by the volunteers contributing to the system and its contents (2006 - 2022)
	                              </td>
	                           </tr>
	                        </table>
	                     </td>
	                  </tr>
	                  <tr>
	                     <td>
	                        <table width="100%" style="padding:0px 30px;">
	                           <tr>
	                              <td style="padding:20px 0px 20px; font-size:12px;" align="center">
	                                 <a href="https://www.canonizer.com/privacypolicy"  target="_blank" style="padding-right: 10px;color:#20395A; text-decoration:none;">Privacy Policy</a>
	                                 <p style="border-right:solid 1px #BDC1C8;display: inline-block; border-left:solid 1px #BDC1C8; padding:0px 10px; color: #76787c;"> Pattern: US 8,160,970 B2</p>
	                                 <a href="https://www.canonizer.com/termservice" target="_blank" style="padding-right: 10px;color:#20395A; text-decoration:none;">Terms & Services</a>
	                              </td>
	                           </tr>
	                        </table>
	                     </td>
	                  </tr>
	               
	            </table>
	            	 </td>
	            </tr>
	            
        </tbody>
    </table>
         
      </center>
   </body>
</html>