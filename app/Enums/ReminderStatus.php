<?php

namespace App\Enums;

enum ReminderStatus: string
{
    /** Waiting for its time to be sent. */
    case Pending = 'pending';

    /** Sent to WhatsApp, waiting for an answer. */
    case Sent = 'sent';

    /** The user asked to be reminded again later. */
    case Snoozed = 'snoozed';

    /** The user answered (taken or skipped). */
    case Answered = 'answered';

    /** No answer in time. */
    case Missed = 'missed';
}
