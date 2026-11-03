<?php

namespace App\Mail;

/**
 * The "add a photo" reminder sent to women, which leads with the choice of
 * who may see the photo (weekly review, 2 Nov 2026: 31 of 144 women show a
 * photo against 43% of men, and the sign-up choice added on 31 Oct only ever
 * reaches the few who join each week).
 *
 * Same variables and the same unsubscribe preference as the general
 * reminder; only the template and the subject differ. Admin-editable
 * template `photo-reminder-women`: where a site has not got it, or an admin
 * switches it off, `members:remind-photo` sends the general reminder
 * instead, so no woman is left out of the reminder altogether.
 */
class PhotoReminderWomenMail extends PhotoReminderMail
{
    protected string $templateSlug = 'photo-reminder-women';

    protected function fallbackSubject(): string
    {
        return 'Add a photo — you choose who can see it';
    }
}
