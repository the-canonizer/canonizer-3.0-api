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
                    <td style="padding:10px 0px 10px;">
                        <p>
                            <a target="_blank"
                                href="<?= \App\Facades\Util::linkForEmail(config('global.APP_URL_FRONT_END') .'/user/supports/'. $data['nick_name_id'] . '?topicnum=&campnum=&namespace=' . $data['namespace_id']) ?>"><?php echo e($data['nick_name']); ?></a>
                            has proposed a change to this <?php echo e($data['type']); ?> <a
                                href="<?php echo e(\App\Facades\Util::linkForEmail($link)); ?>"><?php echo e($data['object']); ?> </a>
                            <?php if(empty($data['is_live']) || $data['is_live'] != 1): ?>
                                which you currently
                                <?php echo e(isset($data['subscriber']) && $data['subscriber'] == 1 ? 'subscribed' : 'directly support'); ?>.
                                If no supporters of this <?php echo e($data['type']); ?>

                                <a href="<?php echo e(\App\Facades\Util::linkForEmail($link)); ?>"><?php echo e($data['object']); ?> </a> object to this change,
                                it will go live in one day / 24 hours
                                <br>
                            <?php endif; ?>
                            <?php if(!empty($data['note'])): ?>
                                <p>Edit summary : <?php echo e($data['note']); ?></p>
                            <?php endif; ?>
                        </p>
                        <p>
                            <?php if(isset($data['subscriber']) && $data['subscriber'] == 1): ?>
                                <h4 style="margin-bottom: 15px;">You are receiving this e-mail because:</h4>
                                <ul style="margin-left: 45px;">
                                    <?php if(isset($data['support_list']) && count($data['support_list']) > 0): ?>
                                        <?php $__currentLoopData = $data['support_list']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $support): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                            <li>You are subscribed to <?php echo \App\Facades\Util::linkForEmail($support); ?></li>
                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                    <?php else: ?>
                                        <li>You are subscribed to <a href="<?php echo e(\App\Facades\Util::linkForEmail(config('global.APP_URL_FRONT_END') . '/' . $data['camp_url'])); ?>">
                                                <?php echo e($data['camp_name']); ?> </a></li>
                                    <?php endif; ?>

                                </ul>
                            <?php else: ?>
                                <h4 style="margin-bottom: 15px;">You are receiving this e-mail because:</h4>
                                <ul style="margin-left: 45px;">
                                    <?php if(isset($data['support_list']) && count($data['support_list']) > 0): ?>
                                        <?php $__currentLoopData = $data['support_list']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $support): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                            <li>You are directly supporting <?php echo \App\Facades\Util::linkForEmail($support); ?></li>
                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                    <?php else: ?>
                                        <li>You are directly supporting <a
                                                href="<?php echo e(\App\Facades\Util::linkForEmail(config('global.APP_URL_FRONT_END') . '/' . $data['camp_url'])); ?>">
                                                <?php echo e($data['camp_name']); ?> </a></li>
                                    <?php endif; ?>

                                    <?php if(isset($data['also_subscriber']) &&
                                        $data['also_subscriber'] == 1 &&
                                        isset($data['sub_support_list']) &&
                                        count($data['sub_support_list']) > 0): ?>
                                        <?php $__currentLoopData = $data['sub_support_list']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $support): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                            <li>You are subscribed to <?php echo \App\Facades\Util::linkForEmail($support); ?></li>
                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                    <?php endif; ?>

                                </ul>

                                <h4 style="margin-top: 15px;">Note:</h4>
                                <p style="margin-top: 15px;">
                                    We request that all <b>direct</b> supporters of a camp continue to receive notifications
                                    and take responsibility for the camp. If you <b>delegate</b> your support to someone
                                    else, you will no longer receive these notifications. <b>Delegating</b> your support to
                                    someone else will also result in your support following them for all camps in this
                                    topic.
                                </p>
                            <?php endif; ?>
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
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.default', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /var/www/html/canonizer-3.0-api/resources/views/emails/purposedToSupporters.blade.php ENDPATH**/ ?>