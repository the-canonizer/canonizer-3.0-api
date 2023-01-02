<?php $__env->startSection('content'); ?>

                 				
<tr>
    <td>
        <table width="100%">
            <tr>
                <td style="text-align:center; padding:30px 0px;"><img src="<?php echo e(URL::asset('assets/images/OTP-img.png')); ?>"></td>
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
              <td>You delegated your support to <a target="_blank" href="<?php echo e(\App\Facades\Util::linkForEmail($data['nick_name_link'])); ?>"><?= $user->nick_name; ?></a> who was directly supporting <a href="<?= \App\Facades\Util::linkForEmail($data['camp_link']); ?>" target="_blank"><?= $data['camp']->camp_name; ?></a> camp in <a href="<?= \App\Facades\Util::linkForEmail($data['topic_link']); ?>" target="_blank"><?= $data['topic']->topic_name; ?></a> topic.</td>
            </tr>
            
            <tr>
               <td>They have entirely removed their support of all camps in this topic, so you have been promoted to a direct supporter in their place. Direct supporters are expected to participate in the maintenance of camps, including receiving and where necessary, responding to emails regarding the maintenance of directly supported camps. If you are not able to do this, you can delegate your support to any other supporter in the <a href="<?= \App\Facades\Util::linkForEmail($data['camp_link']); ?>" target="_blank"><?= $data['camp']->camp_name; ?></a> camp. Or you can entirely remove your support of all camps <a href="<?= \App\Facades\Util::linkForEmail($data['support_link']); ?>" target="_blank">here</a>.</a></td>
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
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.default', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /var/www/html/canonizer-3.0-api/resources/views/emails/promotedDelegatesToDirectMail.blade.php ENDPATH**/ ?>