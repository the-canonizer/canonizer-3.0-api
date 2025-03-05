<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\TermAndServices;

class UpdateTermAndServicesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $frontEndUrl = env('APP_URL_FRONT_END', 'https://canonizer.com');
        $parsedUrl = parse_url($frontEndUrl);
        $domain = $parsedUrl['host'] ?? 'canonizer.com';

        $htmlContent = '<div class="right-whitePnl"><div class="container-fluid">
            <h5 class="font-bold mb-2">Terms of Use for <a href="' . $frontEndUrl . '">' . $domain . '</a></h5>
            <p>Welcome to <a href="' . $frontEndUrl . '">' . $domain . '</a>. By accessing or using our website, you agree to these Terms of Use. Please read them carefully.</p>

            <p class="font-bold">1. Your Rights</p>
            <p>You are free to:</p>
            <ul class="list-disc pl-12">
                <li><p><strong>Access Content: </strong>Read and print our articles and other media free of charge.</p></li>
                <li><p><strong>Share and Reuse: </strong>Share and reuse our articles and media under free and open licenses.</p></li>
                <li><p><strong>Contribute: </strong>Contribute to and edit our pages and projects.</p></li>
            </ul>

            <p class="font-bold">2. Conditions of Use</p>
            <p>When using <a href="' . $frontEndUrl . '">' . $domain . '</a>, you agree to the following conditions:</p>
            <ul class="list-disc pl-12">
                <li><p><strong>Responsibility: </strong>You are responsible for your edits and contributions.</p></li>
                <li><p><strong>Civility: </strong>Maintain a civil environment and refrain from harassing other users.</p></li>
                <li><p><strong>Lawful Behavior: </strong>Do not violate copyright or other applicable laws.</p></li>
                <li><p><strong>No Harm: </strong>Do not harm our technological infrastructure.</p></li>
                <li><p><strong>Adherence to Policies: </strong>Comply with these Terms of Use and applicable community policies.</p></li>
            </ul>

            <p class="font-bold">3. Understanding and Licensing</p>
            <ul class="list-disc pl-12">
                <li><p><strong>Free Licensing: </strong>Contributions and edits must generally be licensed under a free and open license unless they are in the public domain.</p></li>
                <li><p><strong>Informational Purpose: </strong>The content is for informational purposes only and does not constitute professional advice.</p></li>
            </ul>

            <p class="font-bold">4. User Identity</p>
            <p>To maintain the integrity of our community, the following guidelines apply:</p>
            <ul class="list-disc pl-12">
                <li><p><strong>Identity Verification: </strong>' . $domain . ' may require users to verify their identities. Users flagged as "not verified" for disruptive behavior may have limited or denied access.</p></li>
                <li><p><strong>Singular Identity: </strong>Each account must belong to a single individual. Multiple accounts or shared accounts are not allowed.</p></li>
                <li><p><strong>Human Identity: </strong>Bots are not permitted.</p></li>
                <li><p><strong>Non-Transferable Accounts: </strong>You may not transfer your account to another person.</p></li>
                <li><p><strong>Accurate Information: </strong>Providing false information about your identity may result in account restrictions or bans.</p></li>
            </ul>

            <p class="font-bold">5. Disruptive Behavior</p>
            <p>The following behaviors are prohibited:</p>
            <ul class="list-disc pl-12">
                <li><p><strong>Censorship: </strong>Attempts to censor other users through any means.</p></li>
                <li><p><strong>Harassment: </strong>Harassing other users.</p></li>
                <li><p><strong>Libel: </strong>Making false and defamatory statements.</p></li>
                <li><p><strong>Hate Speech: </strong>Engaging in hate speech.</p></li>
                <li><p><strong>Multiple Identities: </strong>Using sock puppets or multiple identities.</p></li>
                <li><p><strong>Username Cloning: </strong>Imitating another user’s username.</p></li>
                <li><p><strong>Holding Camps Hostage: </strong>Refusing to allow modifications to camps in which you participate.</p></li>
            </ul>

            <p class="font-bold">6. Censorship Policy</p>
            <p>' . $domain . ' is committed to avoiding censorship and promoting free expression. However, behavior that disrupts legitimate discourse will be addressed to ensure an inclusive and productive environment for all users.</p>

            <p class="font-bold">7. Enforcement and Amendments</p>
            <p>' . $domain . ' reserves the right to enforce these Terms of Use and to modify them at any time. Continued use of the site after changes constitutes acceptance of the updated terms.</p>

            <p class="font-bold">Contact Us</p>
            <p>If you have any questions about this policy, please contact us at:</p>
            <p><a href="mailto:support@' . $domain . '">support@' . $domain . '</a></p>
        </div></div>';

        TermAndServices::updateOrCreate(
            ['id' => 1],
            ['terms_and_services_content' => $htmlContent]
        );
    }
}
