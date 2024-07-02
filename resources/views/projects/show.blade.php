<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ $project->name }} - Chat
        </h2>
    </x-slot>

    <div class="h-[calc(100vh-160px)] overflow-hidden">
        <div class="h-full max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-1">
            <div class="flex h-full">
                <div class="w-2/3 h-full pr-4"> <!-- Changed from w-3/4 to w-2/3 -->
                    <div class="h-full bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="h-full p-3 bg-white border-b border-gray-200">
                            <livewire:project-chat :project="$project" />
                        </div>
                    </div>
                </div>
                <div class="w-1/3 h-full overflow-y-auto bg-white shadow-sm sm:rounded-lg p-3"> <!-- Changed from w-1/4 to w-1/3 -->
                    <livewire:project-sidebar :project="$project" wire:poll.10s/>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
