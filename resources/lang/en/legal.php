<?php

return [
    'terms' => [
        'title' => 'Terms of use',
        'body' => 'You must be at least 18 to submit a confession or comment. Share your own experience and only submit text you have the right to publish. A moderator reviews submissions. Once published, a story can be read by anyone, shared by link and found through search engines.',
        'sections' => [
            [
                'heading' => 'What happens when you submit?',
                'text' => 'You can write without an account. By submitting, you allow moderators to review your text and the site to display the approved version. You keep the rights to your own writing. Publication, continued availability and a particular review time are not guaranteed.',
            ],
            [
                'heading' => 'Keeping control of your story',
                'text' => 'Keep your recovery code somewhere only you can access. You can withdraw a confession from its management page. If a moderator hides your post, you can use that page to explain why you think the decision should be reconsidered.',
            ],
        ],
    ],
    'community' => [
        'title' => 'Community guidelines',
        'body' => 'There’s a person behind every story. Speak from experience and take your cue from the writer when offering advice. Threats, harassment, insults, spam and details that identify someone else have no place here. Use the report option beside a story or reply if something concerns you.',
        'sections' => [
            [
                'heading' => 'Replying to someone',
                'text' => 'If you disagree, say which idea you see differently. Avoid diagnosing the writer, demanding proof of their experience or guessing their identity. Sharing what happened to you leaves room for someone else’s experience.',
            ],
            [
                'heading' => 'Protecting people in a story',
                'text' => 'Remove details that could identify someone from a private conversation or a combination of events. Take particular care with a child’s school, address or routine. Report a harmful reply from the reply itself so the reviewer sees the right text.',
            ],
        ],
    ],
    'privacy' => [
        'title' => 'Privacy notice',
        'body' => 'We store the text you submit, your chosen topic and any name you choose to display. Moderators can read submissions before publication. If you create an account, we use your email address for account access. Leaving your name blank is an option, but details in a story can still identify you or someone else.',
        'sections' => [
            [
                'heading' => 'Who can see what?',
                'text' => 'Readers see the approved text and the name displayed alongside it. Authorised moderators review pending submissions, report details and appeals. If you have an account, your email supports sign-in, recovery and notifications you choose.',
            ],
            [
                'heading' => 'Reading statistics and ownership',
                'text' => 'If you allow counting, a hash specific to each story helps distinguish repeat opens in the same browser. Separate recovery and device credentials help verify ownership. Withdrawing a public post and requesting erasure of personal data are separate actions.',
            ],
            [
                'heading' => 'When you report something',
                'text' => 'Your explanation helps moderators assess the specific item and the reason you selected. It is not published as a comment. You can turn off optional email notifications in your account settings. Use the privacy request page for questions about retention periods or recipients of your data.',
            ],
        ],
    ],
    'cookies' => [
        'title' => 'Cookie notice',
        'body' => 'Browser cookies maintain your session and language choice. The reader cookie is used for up to :retention_days days only after you allow it, to count opens of public confessions. You can read every confession if you decline. The associated reader hash is deleted after :retention_days days of inactivity; the counter measures page opens, not completed reads.',
        'sections' => [
            [
                'heading' => 'Using a shared device',
                'text' => 'Remembering a device lets someone using the same browser manage the submission. Leave this option off on a shared device. Before clearing browser data, save your recovery code somewhere private.',
            ],
            [
                'heading' => 'Changing your choice',
                'text' => 'Use the controls below to allow or stop future reading measurements in this browser. This choice does not give permission for advertising. A different browser may ask for its own preference.',
            ],
        ],
    ],
    'illegal_content' => [
        'title' => 'Report illegal content',
        'body' => 'If you believe a story or reply is unlawful, open its report menu and choose the illegal content option. Identify the passage and explain why you believe it is unlawful. Leave out recovery codes and personal details unrelated to your report.',
        'sections' => [
            [
                'heading' => 'What to include',
                'text' => 'Choose privacy, copyright, a court order or another reason. Briefly explain the right or rule involved and how the passage affects it. Where relevant, identify the country, original work or order. Avoid repeating sensitive details in full.',
            ],
            [
                'heading' => 'After you send a notice',
                'text' => 'The notice is linked to the item you selected and sent for review. A moderator may hide the item or leave it available. For a community-rule concern, use the ordinary report option. If someone is in immediate danger, contact local emergency services directly.',
            ],
        ],
    ],
    'data_request' => [
        'title' => 'Privacy request',
        'body' => 'To withdraw your story, open its management page. If you’ve lost access, use your saved recovery code. Withdrawal removes the story from public view on :site_name. Copies saved elsewhere and search results may remain for a while. If someone else has published your personal details, report their post.',
        'sections' => [
            [
                'heading' => 'Preparing a request',
                'text' => 'Use the privacy contact below to explain what you want and which data you mean. A public link, language and approximate date may be enough. Leave passwords, recovery codes and copies of identity documents out of your first message.',
            ],
            [
                'heading' => 'What happens next',
                'text' => 'We may ask for enough information to verify the connection without disclosing someone else’s data. Where the GDPR applies, the usual response period is one month. If a lawful extension is necessary, you will be told why within that first month.',
            ],
        ],
    ],
    'operator' => ['title' => 'Service operator', 'body' => ':site_domain publishes anonymous stories and reader replies. Moderators review submissions and handle reports. To raise a concern about a particular post, use the report option attached to it.'],
    'rights' => 'Depending on the law that applies to you, you may ask to access, correct or erase your data, restrict its use, object to processing or complain to a data protection authority. Say which data your request concerns and what you would like done.',
    'contact_unavailable' => 'The privacy contact address has not been published yet.',
    'contact_label' => 'Privacy contact',
];
