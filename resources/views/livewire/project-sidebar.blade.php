<div class="bg-gray-800 text-white w-full min-h-screen p-6" wire:poll.8s>
    <h2 class="text-2xl font-bold mb-6 text-blue-300">{{ $project->name }}</h2>

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
        <pre class="text-gray-300 whitespace-pre-wrap">{{ $project->tasks }}</pre>
    </div>
</div>
