<?php

namespace App\Jobs;

use App\Models\Project;
use App\Services\AIService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldBeUnique;

class SendMessageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public $timeout = 90 * 5;

    /**
     * Create a new job instance.
     */
    public function __construct(private readonly Project $project, private readonly string $message)
    {

    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $ai = new AIService($this->project);
        $ai->sendMessage($this->message);
    }
}
