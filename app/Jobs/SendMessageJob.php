<?php

namespace App\Jobs;

use App\Models\Image;
use App\Models\Message;
use App\Models\Project;
use App\Services\AIService;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

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
    public function __construct(private readonly Project $project, private readonly string $message, private array $images = [])
    {
        $ai = new AIService($this->project);
        $message = $ai->appendMessage($this->message, 'user');
        foreach ($images as $image) {
            $data = [
                'message_id' => $message->id,
                'media_type' => $image['media_type'],
                'content' => $image['content'],
            ];
            Image::create($data);
        }
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $ai = new AIService($this->project);
            $ai->sendMessage(null);
        } catch (Exception $e) {
            Message::create([
                'content' => $e->getMessage(),
                'role' => 'error',
                'project_id' => $this->project->id,
            ]);
            Log::error($e->getMessage().' ('.$e->getFile().':'.$e->getLine().')');
        }

    }
}
