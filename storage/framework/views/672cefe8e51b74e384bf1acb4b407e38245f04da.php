<!DOCTYPE html>
<html>
	<head>
        <?php echo $__env->make('layouts.head', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?> 
	</head>
	<body style="font-family: 'Ubuntu', sans-serif;background-color: #F8F8F8; background-image:  url('<?php echo e(url('assets/images/email-bg.png')); ?>'); background-repeat: no-repeat; ">
		<center class="wrapper" style="background-color: transparent !important;">
        	<?php echo $__env->make('layouts.header', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>    
			<table class="main" style="width:100%; background-color: #F8F8F8; border-radius: 15px 15px 0px 0px; margin-top: 30px;">
				<tbody>    
					<?php echo $__env->yieldContent('content'); ?>
				</tbody>
              
                <tfoot>                  
					<?php echo $__env->make('layouts.footer', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>              
                </tfoot>              
			</table>
		</center>
	</body>
</html><?php /**PATH /var/www/html/canonizer-3.0-api/resources/views/layouts/default.blade.php ENDPATH**/ ?>