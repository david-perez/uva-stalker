<?php

namespace App\Console\Commands;

use App\Events\NewSubmission;
use App\Repositories\UVaUsersRepository;
use App\Submission;
use App\UVaUser;
use Hunter\Hunter;
use Illuminate\Console\Command;

class FetchSubmissions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'submissions:fetch';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetch new submissions from stalked UVa users';

    private $UVaUsersRepository;
    private $hunter;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(UVaUsersRepository $UVaUsersRepository, Hunter $hunter)
    {
        parent::__construct();

        $this->UVaUsersRepository = $UVaUsersRepository;
        $this->hunter = $hunter;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $users = $this->UVaUsersRepository->stalkedUsers();

        $this->info("Fetching submissions for {$users->count()} UVaUsers.\n");

        foreach ($users as $u) {
            echo "{$u->username}... ";
            $submissions = $this->getSubmissions($u);
            echo count($submissions) . " new submissions! \n";

            foreach ($submissions as $s) {
                event(new NewSubmission(new Submission($s)));
            }
        }

        echo "\n";
    }

    private function getSubmissions(UVaUser $u)
    {
        $latestSubmission = Submission::where('user', $u->uvaID)->latest('time')->first();

        if ($latestSubmission) {
            $submissions = $this->hunter->userSubmissions($u->uvaID, $latestSubmission->id);
        } else { // It's the first submission we stalk; ask for all submissions.
            $submissions = $this->hunter->userSubmissions($u->uvaID);
        }

        return $submissions;
    }
}
