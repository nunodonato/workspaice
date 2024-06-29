<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Coise</title>
</head>
<body class="antialiased">
<div class="relative sm:flex sm:justify-center sm:items-center min-h-screen bg-dots-darker bg-center bg-gray-100 dark:bg-dots-lighter dark:bg-gray-900 selection:bg-red-500 selection:text-white">
    <div class="text-center">
        <h1 class="text-4xl font-bold text-gray-800 dark:text-white">Projects</h1>
    </div>
    <!-- buttont to create a new project -->
    <a href="{{ route('project.create') }}" class="block bg-blue-500 hover:bg-blue-400 text-white font-semibold text-sm text-center rounded-lg p-2 mt-4">Create New Project</a>
@foreach($projects as $project)
    <div class="bg-white dark:bg-gray-800 shadow-lg rounded-lg overflow-hidden w-80 sm:w-96 mx-2 my-4">
        <img class="w-full h-56 object-cover object-center" src="{{ $project->image }}" alt="{{ $project->title }}">
        <div class="p-4">
            <h1 class="text-2xl font-bold text-gray-800 dark:text-white">{{ $project->title }}</h1>
            <p class="text-sm text-gray-600 dark:text-gray-300">{{ $project->description }}</p>
            <a href="{{ route('project.show', $project->id) }}" class="block bg-blue-500 hover:bg-blue-400 text-white font-semibold text-sm text-center rounded-lg p-2 mt-4">Open Project</a>
        </div>
    </div>
@endforeach
</div>
</body>
</html>
