<?php

namespace App\Listeners;

use App\Events\NewSubmission;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Telegram\Bot\Laravel\Facades\Telegram;

class SubmissionListener
{
    /**
     * Handle the event.
     *
     * @param  NewSubmission  $event
     * @return void
     */
    public function handle(NewSubmission $event)
    {
        $submission = $event->submission;
        $submission->save();

        // Notify all Telegram chats stalking the UVaUser that made the submission.
        $stalks = $submission->uvaUser->stalks;
        foreach ($stalks as $s) {
            // Only notify if the chat started stalking before the submission was sent.
            $chat = $s->associatedChat;
            if ($chat->createdAt < $submission->time) {
                Telegram::setAsyncRequest(true)->sendMessage(['chat_id' => $chat->chatID, 'text' => 'hii', 'parse_mode' => 'Markdown']);
            }
        }
    }
}
