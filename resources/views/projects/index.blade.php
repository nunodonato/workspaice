<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Projects') }}
        </h2>
    </x-slot>

    <div class="">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <img src="images/logo-text.png" class="mx-auto"/>
            <div class="mb-6 mt-12 flex justify-between items-center">
                <h3 class="text-lg font-semibold text-gray-700">Your Projects</h3>
                <div class="space-x-4">
                    <a href="{{ route('settings.index') }}" class="bg-gray-500 hover:bg-gray-800 text-white font-bold py-2 px-4 rounded transition duration-300 ease-in-out transform hover:scale-105">
                        Settings
                    </a>
                    <a href="{{ route('projects.create') }}" class="bg-emerald-600 hover:bg-emerald-800 text-white font-bold py-2 px-4 rounded transition duration-300 ease-in-out transform hover:scale-105">
                        Create New Project
                    </a>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                @forelse($projects as $project)
                    <div class="bg-white overflow-hidden shadow-sm rounded-lg hover:shadow-md transition duration-300 ease-in-out">
                        <div class="p-6">
                            <h3 class="text-xl font-semibold text-gray-800 mb-2">{{ $project->name }}</h3>
                            <p class="text-gray-600 mb-4 line-clamp-2">{{ $project->description }}</p>
                            <div class="flex justify-between items-center">
                                <span class="text-sm text-gray-500">Created: {{ $project->created_at->format('M d, Y') }}</span>
                                <a href="{{ route('projects.show', $project) }}" class="bg-cyan-600 hover:bg-cyan-800 text-white font-bold py-2 px-4 rounded transition duration-300 ease-in-out">
                                    Open
                                </a>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="col-span-full bg-gray-100 rounded-lg p-6 text-center">
                        <p class="text-gray-600">No projects found. Create your first project to get started!</p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</x-app-layout>
