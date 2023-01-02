<?php $__env->startSection('content'); ?>


    <tr>
        <td>
            <table width="100%">
                <tr>
                    <td style="text-align:center; padding:30px 0px;"><img
                            src="<?php echo e(URL::asset('assets/images/OTP-img.png')); ?>"></td>
                </tr>
            </table>
        </td>
    </tr>
    <tr>
        <td>
            <table width="100%" style=" background:#fff; padding:0px 30px;" cellpadding="0">
                <tr>
                    <td style="padding:30px 0px 20px; font-weight:600;">Hello <?php echo e($user->first_name); ?>

                        <?php echo e($user->last_name); ?>, </td>
                </tr>

                <tr>
                    <td style="padding-top: 0px;  font-weight:400;">
                        <?php if($data['action'] == "remove") { ?>
                            <p>
                                <?php if(isset($data['notify_delegated_user']) && $data['notify_delegated_user']){ ?>
                                    <a target="_blank" href="<?php echo e(\App\Facades\Util::linkForEmail($data['nick_name_link'])); ?>"><?php echo e($data['nick_name']); ?></a>
                                        has removed their delegated support from you in this topic: <a target="_blank" href="<?php echo e(\App\Facades\Util::linkForEmail($data['camp_link'])); ?>"><?php echo e($data['topic_name']); ?></a></b>
                                <?php } else{ ?>
                                    You have removed your delegated support from <a target="_blank"
                                    href="<?php echo e(\App\Facades\Util::linkForEmail($data['delegated_nick_name_link'])); ?>"><?php echo e($data['delegated_nick_name']); ?></a> in this topic: <a href="<?php echo e(\App\Facades\Util::linkForEmail($data['camp_link'])); ?>"><b><?php echo e($data['topic_name']); ?></b></a>
                                <?php } ?>
                            </p>
                        <?php }else{ ?>
                            <p>
                                <?php if(isset($data['notify_delegated_user']) && $data['notify_delegated_user']){ ?>
                                    <a target="_blank" href="<?php echo e(\App\Facades\Util::linkForEmail($data['nick_name_link'])); ?>"><?php echo e($data['nick_name']); ?></a>
                                        has delegated their support to you in this topic: <a target="_blank" href="<?php echo e(\App\Facades\Util::linkForEmail($data['camp_link'])); ?>"><?php echo e($data['topic_name']); ?></a></b>
                                <?php } else{ ?>
                                    You have delegated your support to <a target="_blank"
                                    href="<?php echo e(\App\Facades\Util::linkForEmail($data['delegated_nick_name_link'])); ?>"><?php echo e($data['delegated_nick_name']); ?></a> in this topic: <a href="<?php echo e(\App\Facades\Util::linkForEmail($data['camp_link'])); ?>"><b><?php echo e($data['topic_name']); ?></b></a>
                                <?php } ?>
                            </p>
                        <?php } ?>
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
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.default', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /var/www/html/canonizer-3.0-api/resources/views/emails/notifyDelegatorAndDelegatedMail.blade.php ENDPATH**/ ?>