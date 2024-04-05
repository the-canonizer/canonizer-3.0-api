<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\PrivacyPolicy;


class UpdatePrivacyPolicySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        //

        $frontEndUrl = env('APP_URL_FRONT_END');
        $frontEndUrlArr = parse_url($frontEndUrl);
        $htmlContent = "<div class=\"right-whitePnl\">\n    <div class=\"container-fluid\">\n        <h5>Canonizer Privacy Policy.</h5>\n\n\n       <p> You may read any Canonizer page without registering an account.</p>\n\n\n        <p>To modify a Canonizer page, you must provide a valid email address and register an account.</p>\n         \n        <p>Multiple accounts violate Canonizer’s one person/one vote principle and are not acceptable. Use of “sock puppets” may result in a temporary or permanent ban.</p>\n         \n        <p>Canonizer collects some information when you:</p>\n        <ul type=\"disc\">\n          <li>Make public contributions.</li>\n          <li>Register an account or update your user page.</li>\n          <li>Use the Canonizer site.</li>\n          <li>Send us emails or participate in a survey or give feedback.</li>\n        </ul>\n        <p>We are committed to:</p>\n        <ul type=\"disc\">\n            <li>Describing how your information may be used or shared in this Privacy Policy.</li>\n            <li>Using reasonable measures to keep your information secure.</li>\n            <li>Never selling your information or sharing it with third parties for marketing purposes.</li>\n            <li>Only sharing your information in limited circumstances, such as to improve Canonizer, to comply with the law, or to protect you and others.</li>\n            <li>Retaining your data for the shortest possible time that is consistent with maintaining, understanding, and improving Canonizer sites, and our obligations under law.</li>\n        </ul>\n\n        <p>Be aware:</p>\n        <ul type=\"disc\">\n            <li>Any content you add or any change that you make to a Canonizer page will be publicly and permanently available.</li>\n            <li>Our community of volunteer editors and contributors is a self-policing and self-censoring body.</li>\n            <li>All users have access to propose edits and change to all Canonizer topic records, camp records, and camp statements.</li>\n            <li>Proposed changes will only be accepted if there is unanimous consent from the supporters of that camp.</li>\n            <li>For the protection of Canonizer.com and other users, if you do not agree with this Privacy Policy, you may not use <a href=\"" . $frontEndUrl . "\">" . $frontEndUrlArr['host'] . ".</a></li>\n      </ul>\n\n\n    </div>\n\n</div>";
        PrivacyPolicy::updateOrCreate(
            ['id' => '1'],
            ['privacy_policy_content' => $htmlContent]
        );
    }
}
