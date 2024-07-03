<div class="bg-gray-800 text-white w-full min-h-screen p-6" x-data="{ activeTab: 'project' }" wire:poll.8s>
    <div class="flex items-center mb-6">
        <h2 class="text-2xl font-bold text-blue-300 mr-2">{{ $project->name }}</h2>
        <button wire:click="openFolder" title="Open Project Folder" class="text-gray-400 hover:text-white mr-2">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" />
            </svg>
        </button>
        <button wire:click="openTerminal" title="Open Terminal" class="text-gray-400 hover:text-white">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 28" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2z" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 12l4 4-4 4" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 20h6" />
            </svg>
        </button>
    </div>

    <div class="mb-4">
        <div class="flex border-b border-gray-700">
            <button @click="activeTab = 'project'" :class="{ 'border-b-2 border-blue-500': activeTab === 'project' }" class="py-2 px-4 text-sm font-medium text-gray-300 hover:text-white">
                Project
            </button>
            <button @click="activeTab = 'snapshots'" :class="{ 'border-b-2 border-blue-500': activeTab === 'snapshots' }" class="py-2 px-4 text-sm font-medium text-gray-300 hover:text-white">
                Snapshots
            </button>
            <button @click="activeTab = 'settings'" :class="{ 'border-b-2 border-blue-500': activeTab === 'settings' }" class="py-2 px-4 text-sm font-medium text-gray-300 hover:text-white">
                Settings
            </button>
        </div>
    </div>

    <div x-show="activeTab === 'project'">
        <div class="mb-2">
            <h3 class="text-lg font-semibold mb-2 text-blue-200">Description</h3>
            <p class="text-gray-300 whitespace-pre-line">{{ $project->description }}</p>
        </div>
        <div class="mb-6 text-sm text-gray-400">
            <p>Created: {{ $project->created_at->format('M d, Y') }}</p>
            <p>Last Updated: {{ $project->updated_at->format('M d, Y H:i') }}</p>
        </div>

        <div class="mb-6">
            <h3 class="text-lg font-semibold mb-2 text-blue-200">Technical Specs</h3>
            <pre class="text-gray-300 whitespace-pre-wrap">{{ $project->technical_specs }}</pre>
        </div>

        <div class="mb-6">
            <h3 class="text-lg font-semibold mb-2 text-blue-200">Tasks</h3>
            <pre class="text-gray-300 whitespace-pre-wrap">{{ $tasks }}</pre>
        </div>
    </div>

    <div x-show="activeTab === 'snapshots'">
        <!-- Snapshots content will go here -->
        <p class="text-gray-300">Snapshots content coming soon...</p>
    </div>

    <div x-show="activeTab === 'settings'">
        <!-- button to delete project with livewire confirmation -->
        <div class="mb-6">
            <button wire:click="deleteProject" wire:confirm="Delete this project and all its files?" class="bg-red-600 hover:bg-red-800 text-white font-bold py-2 px-4 rounded transition duration-300 ease-in-out transform">
                Delete Project
            </button>
    </div>
</div>
