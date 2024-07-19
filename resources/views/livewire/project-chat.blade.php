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
                            $textAlignClass = 'text-left';
                        } else if ($isAssistant) {
                            $mergeMessage = false;
                            $messageClass = 'bg-sky-200';
                            $prevRole = 'assistant';
                            $textAlignClass = 'text-left';
                        } else if ($message->role == 'error') {
                            $mergeMessage = false;
                            $messageClass = 'bg-red-300 mx-auto text-center';
                            $prevRole = 'error';
                            $textAlignClass = 'text-center';
                        } else {
                            $mergeMessage = false;
                            $messageClass = 'bg-emerald-200';
                            $prevRole = 'user';
                            $textAlignClass = 'text-right';
                        }

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
                            case 'assistant':
                                $content = $message->content;

                            default:
                                $content = $message->content;
                                break;
                        }
                @endphp


                    <div class="mb-4 {{ $textAlignClass }}">
                        <div class="inline-block max-w-[75%] p-2 rounded-lg text-left {{ $messageClass }} shadow shadow-lg">
                            <span class="font-bold text-xs uppercase">{{ $message->role == 'user' ? 'Me' : $message->role }}</span>
                            <p class="mt-1 whitespace-pre-wrap">{{ $content }}</p>
                            @if($message->images)
                                <div class="mt-2 flex flex-wrap gap-2">
                                    @foreach($message->images as $image)
                                        <img src="data:{{$image->media_type}};base64, {{$image->content}}" alt="Attached Image" class="max-h-[300px] rounded">
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach

        </div>
    </div>
    <div class="mt-2">
        <form wire:submit.prevent="sendMessage" id="chat-form">
            <div class="flex flex-col space-y-2">
                @if(count($images) > 0)
                    <div class="flex flex-wrap gap-2">
                        @foreach($images as $index => $image)
                            <div class="relative">
                                <img src="{{ $image->temporaryUrl() }}" alt="Selected Image" class="w-20 h-20 object-cover rounded">
                                <button type="button" wire:click="removeImage({{ $index }})" class="absolute top-0 right-0 bg-red-500 text-white rounded-full w-5 h-5 flex items-center justify-center">
                                    &times;
                                </button>
                            </div>
                        @endforeach
                    </div>
                @endif
                <div class="flex">
                    <label for="image-upload" class="cursor-pointer px-4 py-2 bg-gray-200 hover:bg-gray-300 rounded-l-lg flex items-center justify-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                    </label>
                    <input id="image-upload" type="file" class="hidden" wire:model="images" multiple accept="image/jpeg,image/png,image/gif,image/webp">
                    <textarea
                        wire:model="newMessage"
                        class="flex-1 p-2 border-t border-b border-l text-gray-800 border-gray-200 bg-white resize-none"
                        placeholder="Type your message... (Enter to add line, Shift+Enter to send)"
                        id="message-input"
                        rows="2"
                        x-data
                        wire:keydown.shift.enter="sendMessage"
                    ></textarea>
                    <button @if($loading) disabled @endif type="submit" class="px-4 rounded-r-lg @if($loading) bg-emerald-200 border-green-200 @else bg-emerald-600 hover:bg-emerald-800 border-green-600 @endif  text-white font-bold p-2 uppercase  border-t border-b border-r">Send</button>
                </div>
            </div>
        </form>
    </div>
</div>
