<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
Create new project
        </h2>
    </x-slot>

    <div class="h-[calc(100vh-160px)] overflow-hidden">
        <div class="h-full max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-1">
            <div class="flex h-full">
                <div class="w-2/3 h-full pr-4"> <!-- Changed from w-3/4 to w-2/3 -->

                            <livewire:project-creation/>

                </div>

            </div>
        </div>
    </div>
</x-app-layout>
