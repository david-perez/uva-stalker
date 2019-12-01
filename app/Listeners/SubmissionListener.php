<?php

namespace App\Listeners;

use Storage;
use App\Events\NewSubmission;
use App\Submission;
use App\UVaUser;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
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


                /*
                 * As of 2018-09-16, curl / fopen / file_get_contents() are unable to interact with uva.onlinejudge.org:443.
                 * This is because SSL certificate verification fails, and is likely to be an issue with how
                 * uva.onlinejudge.org is reporting the certificate chain.
                 *
                 * curl: (60) SSL certificate problem: unable to get local issuer certificate
                 *
                 * The command openssl s_client -connect uva.onlinejudge.org:443 yields
                 * verify error:num=21:unable to verify the first certificate
                 *
                 * Likely related to https://stackoverflow.com/questions/7587851/openssl-unable-to-verify-the-first-certificate-for-experian-url,
                 * which explains why uva.onlinejudge.org:443 is reachable via a web browser, for example.
                 *
                 * Since I don't know a way of indicating Telegram::sendDocument() to disable SSL certificate verification
                 * when downloading the document from uva.onlinejudge.org, the workaround as of now consists in using
                 * an HTTP client to download the PDF disabling SSL certificate verification, store it publicly and temporarily
                 * in the server's filesystem, and provide the public URL of the stored file to Telegram::sendDocument().
                 *
                 * Don't forget to symlink Laravel's public storage to public/storage. Refer to https://laravel.com/docs/5.5/filesystem#the-public-disk.
                 */

                // Send the problem statement as a PDF.
                // First, download the PDF from the UVa servers.
                $client = new Client();
                $request = new Request('GET', $this->getProblemPdfUrl($problem['number']));
                $response = $client->send($request, ['verify' => false]);
                $pdfContents = $response->getBody()->getContents();

                // Temporarily store the PDF in the file system.
                $filename = $problem['number'] . '.pdf';
                Storage::disk('public')->put($filename, $pdfContents);

                // Send the message with the PDF.
                Telegram::setAsyncRequest(false)->sendDocument([
                    'chat_id' => $chat->chatID, 'document' => Storage::disk('public')->url($filename)
                ]);

                // Delete the PDF.
                Storage::disk('public')->delete($filename);

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

        // We subtract five minutes for good measure because UVa OJ server's time is ~6 mins ahead of UTC.
        $body1 = 'Submitted on *' . $submission->time . ' UTC* in ' . SubmissionListener::LANGUAGES[$submission->language];
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

    private function getProblemTitleMarkdown($problem): String
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
