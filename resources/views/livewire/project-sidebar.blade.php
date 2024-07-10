@php use Illuminate\Support\Number; @endphp
<div
    x-data="{
        activeTab: 'project',
        showFileBrowser: false,
        toggleFileBrowser() {
            this.showFileBrowser = !this.showFileBrowser;
            if (this.showFileBrowser) {
                $wire.browseFiles();
            }
        }
    }"
    class="bg-gray-800 text-white w-full min-h-screen p-6"
    wire:poll.8s
>
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
            <button @click="activeTab = 'files'" :class="{ 'border-b-2 border-blue-500': activeTab === 'files' }" class="py-2 px-4 text-sm font-medium text-gray-300 hover:text-white">
                Files
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

    <div x-show="activeTab === 'files'">
        <div class="mb-6">
            <h3 class="text-lg font-semibold mb-2 text-blue-200">Sticky files</h3>
            <button
                @click="toggleFileBrowser()"
                class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded cursor-pointer inline-block mb-4"
            >
                Add New Files
            </button>

            <ul class="space-y-2">
                @foreach($files as $index => $file)
                    <li class="flex items-center justify-between bg-gray-700 p-2 rounded">
                        <span title="{{ $file['full_path'] }}" class="truncate">{{ $file['name'] }} ({{ Number::fileSize($file['size']) }})</span>
                        <button wire:click="removeFile({{ $index }})" class="text-red-400 hover:text-red-600">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                            </svg>
                        </button>
                    </li>
                @endforeach
            </ul>
        </div>

        <!-- File Browser Modal -->
        <div x-show="showFileBrowser" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full" x-cloak>
            <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-gray-700">
                <div class="mt-3 text-center">
                    <h3 class="text-lg leading-6 font-medium text-white">File Browser</h3>
                    <div class="mt-2 px-7 py-3">
                        <p class="text-sm text-gray-300">Current Path: {{ $currentPath }}</p>
                        <ul class="mt-4 space-y-2 text-left">
                            @foreach($directoryContents as $item)
                                <li>
                                    @if($item['type'] === 'dir')
                                        <button wire:click="browseFiles('{{ $item['path'] }}')" class="text-blue-400 hover:underline">
                                            📁 {{ $item['name'] }}
                                        </button>
                                    @else
                                        <button wire:click="addFile('{{ $item['path'] }}')" class="text-green-400 hover:underline">
                                            📄 {{ $item['name'] }} ({{ Number::fileSize($item['size']) }})
                                        </button>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    </div>
                    <div class="items-center px-4 py-3">
                        <button @click="toggleFileBrowser()" class="px-4 py-2 bg-gray-500 text-white text-base font-medium rounded-md w-full shadow-sm hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-gray-300">
                            Close
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div x-show="activeTab === 'snapshots'">
        <!-- Snapshots content will go here -->
        <button wire:confirm wire:click="createSnapshot" class="bg-blue-600 hover:bg-blue-800 text-white font-bold py-2 px-4 rounded transition duration-300 ease-in-out transform">
            New Snapshot
        </button>

        <!-- display session flashed messages -->
        @if (session()->has('snapshot'))
            <div class="text-green-400 p-4 rounded-lg mt-4">
                {{ session('snapshot') }}
            </div>
        @endif

        @forelse($snapshots as $snapshot)
            <div class="flex items-center justify-between p-4 rounded-lg mb-2">
                <div>
                    <h3 class="text-lg font-semibold text-blue-200">{{ $snapshot->name }}</h3>
                    <p class="text-gray-300">{{ $snapshot->created_at->format('M d, Y H:i') }}</p>
                </div>
                <div>
                    <button wire:confirm="Are you sure? All changes done after this snapshot will be lost." wire:click="restoreSnapshot({{ $snapshot->id }})" class="bg-sky-600 hover:bg-sky-800 text-white font-bold py-0 px-2 rounded transition duration-300 ease-in-out transform">
                        Restore
                    </button>
                </div>
            </div>
        @empty
            <p class="text-gray-300">No snapshots found.</p>
        @endforelse
    </div>

    <div x-show="activeTab === 'settings'">
        <!-- show stats (input tokens and output token used) -->
        <div class="mb-6">
            <h3 class="text-lg font-semibold mb-2 text-blue-200">Stats</h3>
            <p class="text-gray-300">Input Tokens: {{ Number::format($inputTokens) }} ({{ Number::currency($inputCost) }})</p>
            <p class="text-gray-300">Output Tokens: {{ Number::format($outputTokens) }} ({{ Number::currency($outputCost) }})</p>
            <p class="text-gray-300 font-semibold mt-2">Total Cost: {{ Number::currency($inputCost + $outputCost) }}</p>
        </div>


        <!-- button to delete project with livewire confirmation -->
        <div class="mb-6">
            <button wire:click="deleteProject" wire:confirm="Delete this project and all its files?" class="bg-red-600 hover:bg-red-800 text-white font-bold py-2 px-4 rounded transition duration-300 ease-in-out transform">
                Delete Project
            </button>
    </div>
</div>
