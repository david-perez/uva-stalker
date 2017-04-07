<?php

namespace App\Listeners;

use App\Events\NewSubmission;
use App\Submission;
use App\UVaUser;
use Carbon\Carbon;
use Hunter\Hunter;
use Hunter\Language;
use Hunter\Status;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Telegram\Bot\Laravel\Facades\Telegram;

class SubmissionListener
{
    const VERDICTS = [
        Status::NO_VERDICT          => 'NO VERDICT',
        Status::SUBMISSION_ERROR    => 'SUBMISSION ERROR',
        Status::CANT_BE_JUDGED      => 'CANT BE JUDGED',
        Status::IN_QUEUE            => 'IN QUEUE',
        Status::COMPILATION_ERROR   => 'CE',
        Status::RESTRICTED_FUNCTION => 'RF',
        Status::RUNTIME_ERROR       => 'RE',
        Status::OUTPUT_LIMIT        => 'OUTPUT LIMIT',
        Status::TIME_LIMIT          => 'TLE',
        Status::MEMORY_LIMIT        => 'MLE',
        Status::WRONG_ANSWER        => 'WA',
        Status::PRESENTATION_ERROR  => 'PE',
        Status::ACCEPTED            => 'AC',
    ];

    const EMOJIS = [
        Status::IN_QUEUE            => '👀',
        Status::COMPILATION_ERROR   => '👷',
        Status::RUNTIME_ERROR       => '💥',
        Status::TIME_LIMIT          => '⏱️',
        Status::MEMORY_LIMIT        => '🔢',
        Status::WRONG_ANSWER        => '❌',
        Status::PRESENTATION_ERROR  => '🖋️️',
        Status::ACCEPTED            => '✅',
    ];

    const LANGUAGES = [
        Language::ANSI_C       => 'ANSI C',
        Language::JAVA         => 'Java',
        Language::CPLUSPLUS    => 'C++',
        Language::PASCAL       => 'Pascal',
        Language::CPLUSPLUS11  => 'C++11',
        Language::PYTHON       => 'Python',
    ];

    private $hunter;

    public function __construct(Hunter $hunter)
    {
        $this->hunter = $hunter;
    }

    /**
     * Handle the event.
     *
     * @param  NewSubmission  $event
     * @return void
     */
    public function handle(NewSubmission $event)
    {
        $submission = $event->submission;

        // Ignore unjudged submissions for which we will receive a verdict later on.
        if ($submission->verdict === Status::IN_QUEUE || $submission->verdict === Status::NO_VERDICT) {
            return;
        }

        $submission->save();

        // Notify all Telegram chats stalking the UVaUser that made the submission.
        $stalks = $submission->uvaUser->stalks;
        foreach ($stalks as $s) {
            // Only notify the chat if the stalk was created by the chat before the submission was sent.
            if ($s->createdAt < $submission->time) {
                $problem = $this->hunter->problem($submission->problem);
                $user = $submission->uvaUser;

                $chat = $s->associatedChat;

                Telegram::setAsyncRequest(false)->sendMessage([
                    'chat_id' => $chat->chatID, 'text' => $this->getTextMessage($problem, $submission, $user), 'parse_mode' => 'Markdown'
                ]);

                // Send the problem statement as a PDF.
                $pdfUrl = $this->getProblemPdfUrl($problem['number']);
                Telegram::setAsyncRequest(false)->sendDocument([
                    'chat_id' => $chat->chatID, 'document' => $pdfUrl, 'caption' => sprintf("%s - %s", $problem['number'], $problem['title'])
                ]);

                // If it was an AC, inform of the current rank of the UVaUser.
                if ($submission->verdict === Status::ACCEPTED) {
                    Telegram::setAsyncRequest(false)->sendMessage([
                        'chat_id' => $chat->chatID, 'text' => $this->getUserRankTextMessage($user), 'parse_mode' => 'Markdown'
                    ]);
                }
            }
        }
    }

    private function getTextMessage($problem, Submission $submission, UVaUser $user): String
    {
        $verdict = $this->getVerdict($submission);

        $header = sprintf('%s by [%s](http://uhunt.felix-halim.net/id/%d) on %s) (dacu: %d)',
            $verdict,
            $user->username,
            $user->uvaID,
            $this->getProblemTitleMarkdown($problem),
            $problem['dacu']);
        $body1 = 'Submitted *' . $submission->time->diffForHumans(Carbon::now(), true) . '* ago in ' . SubmissionListener::LANGUAGES[$submission->language];
        $body2 = sprintf('runtime: _%d ms_, tl: _%d ms_, best: _%d ms_', $submission->runtime, $problem['limit'], $problem['bestRuntime']);
        if ($submission->rank != -1) {
            $body2 .= ", rank: _{$submission->rank}_";
        }

        return "$header\n$body1\n$body2";
    }

    private function getVerdict($submission): String
    {
        $emoji = '';
        if (array_key_exists($submission->verdict, SubmissionListener::EMOJIS)) {
            $emoji = SubmissionListener::EMOJIS[$submission->verdict];
        }

        if ($emoji !== '') {
            return $emoji . ' ' . SubmissionListener::VERDICTS[$submission->verdict];
        } else {
            return SubmissionListener::VERDICTS[$submission->verdict];
        }
    }

    private function getProblemTitleMarkdown($problem)
    {
        return sprintf('[%s - %s](https://uva.onlinejudge.org/index.php?option=onlinejudge&page=show_problem&problem=%d)',
            $problem['number'],
            $problem['title'],
            $problem['id']);
    }

    private function getProblemPdfUrl($problemNumber): String
    {
        $volume = substr($problemNumber, 0, -2); // Everything except the last two digits.
        return sprintf('https://uva.onlinejudge.org/external/%s/%s.pdf', $volume, $problemNumber);
    }

    private function getUserRankTextMessage(UVaUser $user): String
    {
        $row = $this->hunter->userRanklist($user->uvaID, 0, 0)[0];
        return sprintf("*%s* is now ranked %d with %d solved problems\n", $user->username, $row['rank'], $row['accepted']);
    }
}
