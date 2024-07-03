<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-2xl  leading-tight">
            {{ $project->name }}
        </h2>
    </x-slot>

    <div class="h-[calc(100vh-100px)] overflow-hidden py-8 text-lg">
        <div class="h-full max-w-[1400px] mx-auto px-4 sm:px-6 lg:px-8 py-1">
            <div class="flex h-full">
                <div class="w-2/3 h-full pr-4"> <!-- Changed from w-3/4 to w-2/3 -->
                    <div class="h-full bg-white overflow-hidden shadow sm:rounded-lg">
                        <div class="h-full p-3 bg-white border-b border-gray-200">
                            <livewire:project-chat :project="$project" />
                        </div>
                    </div>
                </div>
                <div class="w-1/3 h-full overflow-y-auto bg-gray-800 shadow sm:rounded-lg"> <!-- Changed from w-1/4 to w-1/3 -->
                    <livewire:project-sidebar :project="$project"/>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
