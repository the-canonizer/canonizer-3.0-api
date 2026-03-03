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
        $frontEndUrl = env('APP_URL_FRONT_END');
        $parsedUrl = parse_url($frontEndUrl);
        $domain = $parsedUrl['host'] ?? 'Canonizer.com';

        $htmlContent = "<div class='right-whitePnl'><div class='container-fluid'>
            <h5 class='font-bold mb-2'>Privacy Policy for <a href='{$frontEndUrl}'>{$domain}</a></h5>
            <p class='font-bold'>Effective Date: 01 February 2025</p>
            <p class='font-bold'>Introduction</p>
            <p><a href='{$frontEndUrl}'>{$domain}</a> values your privacy and is committed to protecting your personal information. This Privacy Policy outlines how we collect, use, disclose, and protect your data when you interact with our website. By using <a href='{$frontEndUrl}'>{$domain}</a>, you agree to the terms outlined in this policy.</p>
            <p class='font-bold'>1. Information Collection</p>
            <p>We collect information in the following ways:</p>
            <ul class='list-disc pl-12'>
                <li><p><strong>Browsing:</strong> You may read any page on <a href='{$frontEndUrl}'>{$domain}</a> without registering an account.</p></li>
                <li><p><strong>Account Registration:</strong> To modify a page, you must register with a valid email address. Creating multiple accounts violates our one person/one vote principle and is prohibited. Using 'sock puppets' may result in a ban.</p></li>
                <li><p><strong>User Contributions:</strong> Information is collected when you make public contributions, update your user page, or interact with the site.</p></li>
                <li><p><strong>Communication:</strong> We collect information when you contact us via email, participate in surveys, or provide feedback.</p></li>
            </ul>
            <p class='font-bold'>2. Use of Information</p>
            <p>Your information is used to:</p>
            <ul class='list-disc pl-12'>
                <li><p>Operate and maintain <a href='{$frontEndUrl}'>{$domain}</a>.</p></li>
                <li><p>Facilitate your participation in the site's features and services.</p></li>
                <li><p>Communicate updates, respond to inquiries, and provide support.</p></li>
                <li><p>Improve site functionality and user experience.</p></li>
                <li><p>Ensure compliance with our policies and legal obligations.</p></li>
            </ul>
            <p class='font-bold'>3. Information Sharing</p>
            <p>We do not sell your personal information or share it with third parties for marketing purposes. We may share information only in limited circumstances:</p>
            <ul class='list-disc pl-12'>
                <li><p><strong>Service Improvement:</strong> To enhance <a href='{$frontEndUrl}'>{$domain}</a> and its features.</p></li>
                <li><p><strong>Legal Compliance:</strong> To comply with legal obligations or protect the rights and safety of users and <a href='{$frontEndUrl}'>{$domain}</a>.</p></li>
                <li><p><strong>Security:</strong> To investigate and prevent potential fraud, abuse, or violations of our policies.</p></li>
            </ul>
            <p class='font-bold'>4. Data Security</p>
            <p>We implement reasonable measures to protect your information from unauthorized access, disclosure, or destruction. However, no internet transmission or electronic storage is completely secure, so we cannot guarantee absolute security.</p>
            <p class='font-bold'>5. Data Retention</p>
            <p>We retain your data only as long as necessary to provide the services on <a href='{$frontEndUrl}'>{$domain}</a>, comply with legal obligations, and improve our site. Your data will be deleted or anonymized when no longer needed.</p>
            <p class='font-bold'>6. User Responsibilities and Public Contributions</p>
            <ul class='list-disc pl-12'>
                <li><p><strong>Public Content:</strong> Any content you add or change on <a href='{$frontEndUrl}'>{$domain}</a> will be publicly and permanently available.</p></li>
                <li><p><strong>Community Governance:</strong> <a href='{$frontEndUrl}'>{$domain}</a> is governed by a community of volunteer editors and contributors who manage and propose changes to topic records, camp records, and camp statements.</p></li>
                <li><p><strong>Proposed Changes:</strong> Changes will be accepted only with unanimous consent from the supporters of the relevant camp.</p></li>
            </ul>
            <p class='font-bold'>7. Acceptance of Terms</p>
            <p>By using <a href='{$frontEndUrl}'>{$domain}</a>, you agree to this Privacy Policy. If you do not agree, you must discontinue use of the site.</p>
            <p class='font-bold'>Contact Us</p>
            <p>If you have any questions about this Privacy Policy, please contact us at </p>
            <p><a href='mailto:support@canonizer.com'>support@canonizer.com</a></p>
        </div></div>";

        PrivacyPolicy::updateOrCreate(
            ['id' => 1],
            ['privacy_policy_content' => $htmlContent]
        );
    }
}
