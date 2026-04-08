<x-layouts.app title="Refund Policy">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <h1 class="text-3xl font-serif font-bold text-gray-900 mb-8">Refund Policy</h1>
        <div class="prose prose-sm max-w-none text-gray-700 space-y-6">
            <p>{{ config('app.name') }} will not refund any payment to any member for any reason whatsoever except in the case of error on {{ config('app.name') }}'s part.</p>

            <p>{{ config('app.name') }} will not refund any member who has decided that they no longer wish to use {{ config('app.name') }}. A refund can NOT be given in part or whole for any subscription used or not used by any member for whatever reason. Users who wish to cancel their subscription are not permitted to seek a partial or full refund for any reason. Please read the full terms for our refund policy below. Agreeing to our terms and conditions when you create an account means you agree to our refund policy.</p>

            <ul class="list-disc pl-5 space-y-3">
                <li>Anyone found to be using the website under the age of 18 will be banned immediately without refund.</li>
                <li>You agree to include FACTUAL information about yourself which is a TRUE and ACCURATE representation of yourself. You may NOT use fake pictures and any other misleading or untruthful personal information. Misrepresentation can result in a permanent ban without refund.</li>
                <li>Abuse to staff will NOT be tolerated in any way, shape or form, and will result in a permanent ban without refund.</li>
                <li>It is your responsibility to communicate with members in a polite, respectful manner. Rude, offensive, or inappropriate messages will result in a permanent ban without refund.</li>
                <li>A refund can NOT be given on the basis of members choosing not to correspond with you.</li>
                <li>It is YOUR responsibility to CANCEL your membership when you are no longer interested in being a member. {{ config('app.name') }} will NOT refund any members who have failed to do so.</li>
                <li>ALL members — whether free or paid — MUST adhere to the rules mentioned in our terms and conditions. Failure to do so can result in a ban without refund.</li>
                <li>Profiles are approved within 24 hours and any profiles deemed suspicious or fraudulent will be rejected immediately with a permanent ban and NO refund.</li>
                <li>You will not engage in gathering personal information such as email addresses, telephone numbers, addresses, financial information or any other kind of information of our members.</li>
            </ul>
        </div>
    </div>
</x-layouts.app>
