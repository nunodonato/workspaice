<?php

namespace App\Services;

use App\Models\Call;
use App\Models\Message;
use App\Models\Project;
use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use NunoDonato\AnthropicAPIPHP\Client;
use NunoDonato\AnthropicAPIPHP\Messages;
use NunoDonato\AnthropicAPIPHP\Tools;

class AIService
{
    public $ai;

    public function __construct(public Project $project)
    {
        $key = Setting::getSetting('api_key');
        if (empty($key)) {
            $key = getenv('ANTHROPIC_API_KEY');
        }
        $this->ai = (new Client($key))->setTimeout(60);
    }

    public function appendMessage(
        string $message,
        string $role,
        $name = null,
        $tool_id = null,
        $input = null,
        $multiple = false
    ): Message {
        $message = [
            'content' => $message,
            'role' => $role,

        ];
        if ($name) {
            $message['name'] = $name;
        }
        if ($tool_id) {
            $message['tool_id'] = $tool_id;
        }
        if ($input) {
            $message['input'] = $input;
        }

        $message['project_id'] = $this->project->id;
        $message['multiple'] = $multiple;

        return Message::create($message);
    }

    public function sendMessage($input = null, $role = 'user', $name = null): void
    {
        $shouldRepeat = true;
        $limit = 20;
        if ($input) {
            $this->appendMessage($input, $role, $name);
        }

        while ($shouldRepeat) {
            $shouldRepeat = false;

            $previousMessages = $this->getPreviousMessages($limit);

            $messages = new Messages();
            foreach ($previousMessages as $i => $message) {
                if (count($messages->messages()) == 0 && $message['role'] != 'user') {
                    if (count($previousMessages) > 45 && $message['role'] != 'tool_result') {
                        $messages->addMessage('user', 'continue');
                    } else {
                        continue;
                    }
                }
                if ($message['role'] == 'tool_use' || $message['role'] == 'tool_result') {
                    $count = count($previousMessages);

                    if ($i < $count - 20) {
                        $message = $this->trimBigMessages($message);
                    }

                    $content = $this->prepareMessageContent($message);
                    $message['role'] = $message['role'] == 'tool_use' ? 'assistant' : 'user';
                    $message['content'] = [$content];
                }

                // if is the last message in the array, append special content
                if ($i == count($previousMessages) - 1) {
                    $message = $this->handleLastMessage($message);
                }

                $messages->addMessage($message['role'], $message['content']);

                if ($message['role'] == 'user') {
                    $this->addUserImages($message['images'], $messages);
                }
            }

            if (count($messages->messages()) == 0
                || (count($messages->messages()) == 1 && $this->project->messages()->count() > 1)) {
                $limit += 10;
                $shouldRepeat = true;

                continue;
            }

            $content = $this->sendMessagesToAI($messages);

            // check if we should abort
            if (Cache::has('stop-'.$this->project->id)) {
                Cache::forget('stop-'.$this->project->id);

                return;
            }

            $shouldRepeat = $this->handleAIResponse($content, $shouldRepeat);
        }
    }

    public function getSystemMessage(): string
    {
        $prompt = file_get_contents(resource_path('prompts/system.txt'));

        $msg = $prompt;
        $msg .= "<ProjectInformation>\n";
        $msg .= "Name: {$this->project->name}\n";
        $msg .= "Path: {$this->project->full_path}\n";
        $msg .= "Description: {$this->project->description}\n";
        $msg .= "Technical Specs: {$this->project->technical_specs}\n";
        $msg .= "</ProjectInformation>\n";

        $filemap = $this->project->full_path.DIRECTORY_SEPARATOR.'.workspaice/filemap';
        $map = 'file';
        if (! file_exists($filemap)) {
            $filemap = $this->project->full_path.DIRECTORY_SEPARATOR.'.workspaice/dirmap';
            $map = 'dir';
        }
        if (file_exists($filemap)) {
            $tag = $map == 'file' ? 'FileList' : 'DirectoryList';
            $msg .= "<$tag>\n";
            $msg .= file_get_contents($filemap);
            $msg .= "</$tag>\n";
        }

        return $msg;
    }

    public function getMetaContent()
    {
        $meta = '';
        if (count($this->project->files ?? [])) {
            $meta .= "<StickyFiles>\n";
            foreach ($this->project->files as $file) {
                $meta .= "<File path='$file'>\n";
                $meta .= file_get_contents($file);
                $meta .= "\n</File>\n";
            }
            $meta .= "</StickyFiles>\n";
        }

        $meta .= "<SystemInformation>\n{$this->project->system_description}\nDate:".now()->format(
            'Y-m-d H:i:s'
        )."\n</SystemInformation>\n";
        $meta .= "<Tasks>\n{$this->project->tasks}\n</Tasks>\n";
        $meta .= "<Notes>\n{$this->project->notes}</Notes>";

        return $meta;
    }

    public function getPreviousMessages(int $limit): array
    {
        $previousMessages = $this->project->messages()
            ->orderByDesc('id')
            ->with('images')
            ->where('role', '!=', 'error')
            ->limit($limit)
            ->get()
            ->map(function ($message) {
                $array = [
                    'content' => $message->content,
                    'role' => $message->role,
                    'images' => $message->images,
                ];
                if ($message->name) {
                    $array['name'] = $message->name;
                }
                if ($message->tool_id) {
                    $array['tool_id'] = $message->tool_id;
                }
                if ($message->input) {
                    $array['input'] = $message->input;
                }

                return $array;
            })->reverse()->toArray();

        // reorder array keys, keeping values in place
        $previousMessages = array_values($previousMessages);

        return $previousMessages;
    }

    public function handleLastMessage(mixed $message): mixed
    {
        $meta = $this->getMetaContent();
        if (is_array($message['content'])) {
            $last = count($message['content']) - 1;
            if ($message['role'] == 'user') {
                $message['content'][$last]['content'] = $meta."\n<user>".$message['content'][$last]['content'].'</user>';
            } else {
                $message['content'][$last]['content'] = $meta."\n".$message['content'][$last]['content'];
            }
        } else {
            if ($message['role'] == 'user') {
                $message['content'] = $meta."\n<user>".$message['content'].'</user>';
            } else {
                $message['content'] = $meta."\n".$message['content'];
            }
        }

        return $message;
    }

    public function addUserImages($images, Messages $messages): void
    {
        foreach ($images as $image) {
            $messages->addUserImageMessageFromBase64($image['content'], $image['media_type']);
        }
    }

    /**
     * @throws \Exception
     */
    public function sendMessagesToAI(Messages $messages): mixed
    {
        $tools = new Tools();
        $tools->addToolsFromArray(getAvailableFunctions());

        $response = $this->ai->messages(
            Client::MODEL_3_5_SONNET,
            $messages,
            $this->getSystemMessage(),
            $tools,
            [],
            4096
        );

        if ($response['type'] == 'error') {
            throw new \Exception($response['error']['message']);
        }

        $prompt_tokens = $response['usage']['input_tokens'];
        $completion_tokens = $response['usage']['output_tokens'];

        Call::create([
            'project_id' => $this->project->id,
            'call' => json_encode($messages),
            'response' => $response,
            'prompt_tokens' => $prompt_tokens,
            'completion_tokens' => $completion_tokens,
            'message_count' => count($messages->messages()),
        ]);

        return $response['content'];
    }

    public function trimBigMessages(mixed $message): mixed
    {
        if (strlen($message['content']) > 1000) {
            $message['content'] = '[removed due to length]';
        }
        if (isset($message['input']) && strlen(json_encode($message['input'])) > 1000) {
            $message['input'] = [
                'input' => 'removed due to length',
            ];
        }

        return $message;
    }

    public function prepareMessageContent(mixed $message): array
    {
        $content = [
            'type' => $message['role'],
        ];

        if ($message['role'] == 'tool_use') {
            $content['input'] = $message['input'];
            $content['id'] = $message['tool_id'];
            $content['name'] = $message['content'];
        }
        if ($message['role'] == 'tool_result') {
            $content['content'] = $message['content'];
            $content['tool_use_id'] = $message['tool_id'];
        }

        return $content;
    }

    public function handleAIResponse(array $content, bool $shouldRepeat): bool
    {
        foreach ($content as $i => $message) {
            if (is_string($message)) {
                $this->appendMessage($message, 'assistant', multiple: false);
                echo 'Assistant: '.$message."\n";

                continue;
            } else {
                switch ($message['type']) {
                    case 'tool_use':
                        $name = $message['name'];
                        $args = $message['input'];
                        $this->appendMessage($message['name'], 'tool_use', $message['id'], $message['id'], $args);
                        try {
                            $result = call_user_func_array($name, [$this->project, ...$args]);
                        } catch (\Throwable $t) {
                            $result = 'Error: '.$t->getMessage();
                        }
                        $this->appendMessage(
                            $result,
                            'tool_result',
                            $message['name'],
                            $message['id'],
                            null,
                            multiple: true
                        );
                        $shouldRepeat = true;
                        break;
                    case 'text':
                        $multiple = count($content) > 1;
                        if (! $multiple) {
                            echo 'Assistant: '.$message['text']."\n";
                        } else {
                            echo 'Think: '.$message['text']."\n";
                            if ($i == 0) {
                                echo 'working..';
                            }
                            echo '.';
                        }
                        $this->appendMessage($message['text'], 'assistant', multiple: $multiple);
                        break;
                }
            }
        }

        return $shouldRepeat;
    }
}
