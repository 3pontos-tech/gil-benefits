<?php

declare(strict_types=1);

return [
    'greeting' => 'Hi, :name!',
    'greeting_anonymous' => 'Hi!',
    'created' => ':greeting Your ticket was created successfully. Ticket number: :protocol.',
    'in_progress' => ':greeting Your ticket :protocol — :subject — was received and our support team is already working on it. We will let you know here as soon as there is news.',
    'reopened' => ":greeting Your ticket :protocol — :subject — was reopened and is back in our team's queue. You will hear from us here as soon as there is a new answer.",
    'resolved' => ':greeting Your ticket :protocol — :subject — was resolved by our team. If you still need help, just reply to this message and we will reopen it.',
    'closed' => ':greeting Your ticket :protocol — :subject — was closed. If you need anything else, just open a new conversation here.',
];
