@php
$prevRole = '';
@endphp
<div class="flex flex-col h-full" wire:poll.2s="loadMessages">
    <div class="flex-grow overflow-hidden ">
        <div class="h-full flex flex-col-reverse overflow-y-auto pb-2">
            @if($loading)
                <div class="inset-0 flex items-center justify-center bg-white bg-opacity-75 z-10">
                    <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-500"></div>
                </div>
            @endif
            @foreach ($messages as $message)
                @php
                // check if the message is the first message of the $project->messages()
                    if ($message->id == $firstMessageId) {
                        continue;
                    }

                        $mergeMessage = false;
                        $isAssistant = in_array($message->role, ['assistant', 'tool_use', 'tool_result']);
                        $isTool = in_array($message->role, ['tool_use', 'tool_result']);
                        $isThinking = $isTool || ($message->role=='assistant' && $message->multiple == 1);
                        if ($isTool || $isThinking) {
                            if (!$debug) {
                                continue;
                            }
                            if ($message->role == 'tool_result') {
                                continue;
                            }
                            if ($prevRole == 'think') {
                                $mergeMessage = true;
                            }
                            $messageClass = 'bg-gray-200';
                            $prevRole = 'think';
                        } else if ($isAssistant) {
                            $mergeMessage = false;
                            $messageClass = 'bg-sky-200';
                            $prevRole = 'assistant';
                            } else {
                            $mergeMessage = false;
                            $messageClass = 'bg-emerald-200';
                            $prevRole = 'user';
                            }

                        $textAlignClass = $isAssistant ? 'text-left' : 'text-right';

                        switch($message->role) {
                            case 'tool_use':
                                $content = $message->content;
                                if ($content == 'saveContentsToFile' || $content == 'getFilesInFolder'
                                 || $content == 'runShellCommand' || $content == 'searchForFile'
                                 || $content == 'getContentsFromFile' || $content == 'getContentFromUrl') {
                                    $input = $message->input;
                                    $content .= '
'.reset($input);

                                 }
                                break;
                            default:
                                $content = $message->content;
                                break;
                        }
                @endphp


                    <div class="mb-4 {{ $textAlignClass }}">
                        <div class="inline-block max-w-[75%] p-2 rounded-lg text-left {{ $messageClass }} shadow shadow-lg">
                            <span class="font-bold text-xs uppercase">{{ $message->role == 'user' ? 'Me' : $message->role }}</span>
                            <p class="mt-1 whitespace-pre-wrap">{{ $content }}</p>
                        </div>
                    </div>

            @endforeach

        </div>
    </div>

    <div class="mt-2">
        <form wire:submit.prevent="sendMessage" id="chat-form">
            <div class="flex">
                <textarea
                    @if($loading) disabled @endif
                    wire:model="newMessage"
                    class="flex-1 rounded-l-lg p-2 border-t mr-0 border-b border-l text-gray-800 border-gray-200 bg-white resize-none"
                    placeholder="Type your message... (Enter to add line, Shift+Enter to send)"
                    id="message-input"
                    rows="2"
                    x-data
                    wire:keydown.shift.enter="sendMessage"
                ></textarea>
                <button @if($loading) disabled @endif type="submit" class="px-4 rounded-r-lg @if($loading) bg-emerald-200 border-green-200 @else bg-emerald-600 hover:bg-emerald-800 border-green-600 @endif  text-white font-bold p-2 uppercase  border-t border-b border-r">Send</button>
            </div>
        </form>
    </div>
</div>
