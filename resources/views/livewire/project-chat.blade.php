<div class="flex flex-col h-full" wire:poll.5s="loadMessages">
    <div class="flex-grow overflow-hidden no-scrollbar">
        <div class="h-full flex flex-col-reverse overflow-y-auto pb-2 no-scrollbar">
            @foreach (array_reverse($messages) as $message)
                @php
                    $isAssistant = $message->role === 'assistant';
                    $messageClass = $isAssistant ? 'bg-gray-200' : 'bg-blue-200';
                    $textAlignClass = $isAssistant ? 'text-left' : 'text-right';
                @endphp

                <div class="mb-2 {{ $textAlignClass }}">
                    <div class="inline-block max-w-3/4 p-2 rounded-lg {{ $messageClass }}">
                        <span class="font-bold text-xs uppercase">{{ $message->role }}:</span>
                        <p class="mt-1 whitespace-pre-wrap">{{ $message->content }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <div class="mt-2">
        <form wire:submit.prevent="sendMessage" id="chat-form">
            <div class="flex">
                <textarea
                    wire:model="newMessage"
                    class="flex-1 rounded-l-lg p-2 border-t mr-0 border-b border-l text-gray-800 border-gray-200 bg-white resize-none"
                    placeholder="Type your message... (Enter to add line, Shift+Enter to send)"
                    id="message-input"
                    rows="2"
                    x-data
                    wire:keydown.shift.enter="sendMessage"
                ></textarea>
                <button type="submit" class="px-4 rounded-r-lg bg-blue-500 text-white font-bold p-2 uppercase border-blue-500 border-t border-b border-r">Send</button>
            </div>
        </form>
    </div>
</div>
