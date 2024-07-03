<?php

use App\Models\Project;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Number;

function getAvailableFunctions(): array
{
    return [
        [
            'name' => 'searchForFile',
            'description' => 'Returns a list of files in the project directory that contain the given string in their filename',
            'input_schema' => [
                'type' => 'object',
                'properties' => [
                    'string' => [
                        'type' => 'string',
                        'description' => 'The string to search for in the filename'
                    ],
                ],
                'required' => ['string']
            ]
        ],
        [
            'name' => 'updateProjectInfo',
            'description' => 'Update the information about the project',
            'input_schema' => [
                'type' => 'object',
                'properties' => [
                    'whatToUpdate' => [
                        'type' => 'string',
                        'enum' => ['description', 'technical_specs', 'system_description', 'notes', 'tasks'],
                        'description' => 'The section to update'
                    ],
                    'newContent' => [
                        'type' => 'string',
                        'description' => 'The new content'
                    ]
                ],
                'required' => ['whatToUpdate', 'newContent']
            ],
        ],
        [
            'name' => 'runShellCommand',
            'description' => 'Run a non-interactive shell command (non-root privileges) in the project directory and return its output (trimmed to the first 100 lines)',
            'input_schema' => [
                'type' => 'object',
                'properties' => [
                    'input' => [
                        'type' => 'string',
                        'description' => 'The shell command to run'
                    ],
                ],
                'required' => ['input']
            ]
        ],
        [
            'name' => 'getContentsFromFile',
            'description' => 'Get the contents of a file not yet in the buffer',
            'input_schema' => [
                'type' => 'object',
                'properties' => [
                    'fullFilePath' => [
                        'type' => 'string',
                        'description' => 'The full path to the file'
                    ],
                ],
                'required' => ['fullFilePath']
            ]
        ],
        [
            'name' => 'saveContentsToFile',
            'description' => 'Write contents to a file in the project directory',
            'input_schema' => [
                'type' => 'object',
                'properties' => [
                    'fullFilePath' => [
                        'type' => 'string',
                        'description' => 'The full path to the file'
                    ],
                    'contents' => [
                        'type' => 'string',
                        'description' => 'The contents to write to the file'
                    ],
                    'mode' => [
                        'type' => 'string',
                        'description' => 'w: replace with new contents. a: append to the existing contents',
                        'enum' => ['w', 'a']
                    ]
                ],
                'required' => ['fullFilePath', 'contents', 'mode']
            ]
        ],
        [
            'name' => 'getTreeFolderStructure',
            'description' => 'Get a tree structure of the folders(no files) in the given path',
            'input_schema' => [
                'type' => 'object',
                'properties' => [
                    'fullFolderPath' => [
                        'type' => 'string',
                        'description' => 'The full path of the folder, to get the tree structure from'
                    ],
                ],
                'required' => ['fullFolderPath']
            ]
        ],
        [
            'name' => 'getFilesInFolder',
            'description' => 'Get a list of all files and folders in a folder',
            'input_schema' => [
                'type' => 'object',
                'properties' => [
                    'fullFolderPath' => [
                        'type' => 'string',
                        'description' => 'The full path of the folder, to get the contents from'
                    ],
                ],
                'required' => ['fullFolderPath']
            ]
        ],
        [
            'name' => 'getContentFromUrl',
            'description' => 'Returns the call response from a given URL',
            'input_schema' => [
                'type' => 'object',
                'properties' => [
                    'url' => [
                        'type' => 'string',
                        'description' => 'The full url path'
                    ],
                ],
                'required' => ['url']
            ]
        ]

    ];
}

function searchForFile($project, string $string): string
{
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($project->full_path));
    $matchingFiles = [];

    foreach ($iterator as $file) {
        if ($file->isFile() && stripos($file->getFilename(), $string) !== false) {
            $matchingFiles[] = $file->getPathname();
        }
    }

    $result = "Matching files with '$string':\n";
    foreach ($matchingFiles as $file) {
        $result .= $file . PHP_EOL;
    }

    return $result;
}

function setFilesForBuffer($project, $files)
{
    $project->files = $files;
    $project->save();
    return "Files set for buffer.";
}

function getContentFromUrl($project, $url)
{
    $response = Http::get($url);

    // get main content without the heading part of html
    $content = $response->body();
    $content = explode('<body>', $content);
    $content = explode('</body>', $content[1]);
    $content = $content[0];


    return $content;
}

function updateProjectInfo($project, $whatToUpdate, $newContent)
{
    switch ($whatToUpdate) {
        case 'description':
            return updateProjectDescription($project, $newContent);
        case 'technical_specs':
            return updateProjectTechnicalSpecs($project, $newContent);
        case 'system_description':
            return updateProjectSystemDescription($project, $newContent);
        case 'notes':
            return updateProjectNotes($project, $newContent);
        case 'tasks':
            return updateProjectTasks($project, $newContent);
    }
}

function addFileToBuffer($project, $filepath)
{
    $filesInBuffer = $project->files ?? [];
    $filesInBuffer[] = $filepath;

    if(count($filesInBuffer) > 5) {
        // keep only the last 5
        $filesInBuffer = array_slice($filesInBuffer, -5);
    }

    $project->files = array_unique($filesInBuffer);
    $project->save();
}

function updateProjectTasks($project, $newTasks)
{
    $project->tasks = $newTasks;
    $project->save();
    return "Tasks updated.";
}

function updateProjectDescription($project, $newDescription)
{
    $project->description = $newDescription;
    $project->save();
    return "Description updated.";
}

function updateProjectTechnicalSpecs($project, $newTechnicalSpecs)
{
    $project->technical_specs = $newTechnicalSpecs;
    $project->save();
    return "Specs updated.";
}

function updateProjectSystemDescription($project, $newSystemDescription)
{
    $project->system_description = $newSystemDescription;
    $project->save();
    return "Description updated.";
}

function updateProjectNotes($project, $newNotes)
{
    $project->notes = $newNotes;
    $project->save();
    return "Notes updated.";
}

function getContentsFromFile($project, $fullFilePath)
{
    if (!file_exists($fullFilePath)) {
        $searchResult = searchForFile($project, basename($fullFilePath));
        return "Error: file does not exist\n\n".$searchResult;
    }

    $filesize = filesize($fullFilePath);
    if ($filesize < 10000) {
        addFileToBuffer($project, $fullFilePath);
    }

    return file_get_contents($fullFilePath);
}

function getTreeFolderStructure($project, $fullFolderPath)
{
    return runShellCommand($project, "tree -d {$fullFolderPath}");
}

function getFilesInFolder($project, $fullFolderPath)
{
    if (!file_exists($fullFolderPath)) {
        return "Error: folder does not exist";
    }
    $files = scandir($fullFolderPath);
    $result = "";

    foreach ($files as $item) {
        // '.' and '..' are the current and parent directories respectively
        if ($item != "." && $item != "..") {
            $fullPath = $fullFolderPath . DIRECTORY_SEPARATOR . $item;
            if (is_file($fullPath)) {
                $filesize = filesize($fullPath);
                if ($filesize) {
                    $filesize = Number::fileSize($filesize);
                }
                $result .= $item . " (File ".($filesize?? '' ).")" . PHP_EOL;
            } elseif (is_dir($fullPath)) {
                $result .= $item . " (Folder)" . PHP_EOL;
            }
        }
    }
    return $result;
}

function saveContentsToFile($project, $fullFilePath, $contents, $mode = 'w')
{
    switch($mode) {
        case 'w':
            $mode = 'w';
            break;
        case 'a':
            $mode = 'a';
            break;
        default:
            return "Error: Invalid mode. Use 'w' or 'a'.";
    }

    // Get the directory path
    $dir = dirname($fullFilePath);

    // make sure the dir is inside the project full_path
    if (stripos($dir, $project->full_path) !== 0) {
        return "Error: Invalid file path. Must be inside the project directory.";
    }

    // Create the directory and all its parent directories if they don't exist
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }

    $file = fopen($fullFilePath, $mode);
    fwrite($file, $contents);
    fclose($file);

    $filesize = filesize($fullFilePath);
    if ($filesize < 10000) {
        addFileToBuffer($project, $fullFilePath);
    }

    return "Content saved.";
}

function runShellCommand(Project $project, $input, $maxLines = 100)
{
    $input = "cd {$project->full_path} && " . $input;
    $input .= " 2>&1";


    $final = "";

    $result = execWithTimeout($input, 20);
    if ($result['timed_out']) {
        return "Error: Command timed out. Was it an interactive command?";
    }
    if ($result['code'] > 0) {
        $final = "Errors running command:\n";
    }
    // trim output to first $maxLines lines
    $output = explode("\n", $result['output']);
    $output = implode("\n", array_slice($output, 0, $maxLines));
    $final .= $output;
    $final = trim($final);
    if (strlen($final) == 0) {
        $final = "Success.";
    }
    return $final;
}

function execWithTimeout($cmd, $timeout = 20) {
    $descriptorspec = array(
        0 => array("pipe", "r"),  // stdin
        1 => array("pipe", "w"),  // stdout
        2 => array("pipe", "w")   // stderr
    );

    $process = proc_open($cmd, $descriptorspec, $pipes);

    if (is_resource($process)) {
        // Set streams to non-blocking mode
        stream_set_blocking($pipes[1], 0);
        stream_set_blocking($pipes[2], 0);

        $output = '';
        $start_time = time();

        do {
            $status = proc_get_status($process);

            // Read from stdout and stderr
            $output .= stream_get_contents($pipes[1]);
            $output .= stream_get_contents($pipes[2]);

            // Check if we've exceeded the timeout
            if (time() - $start_time > $timeout) {
                proc_terminate($process);
                return array('output' => $output, 'timed_out' => true, 'code' => $status['exitcode']);
            }

            usleep(100000); // Sleep for 0.1 seconds to reduce CPU usage
        } while ($status['running']);

        // Close all pipes
        fclose($pipes[0]);
        fclose($pipes[1]);
        fclose($pipes[2]);

        // Close the process
        proc_close($process);

        return array('output' => $output, 'timed_out' => false, 'code' => $status['exitcode']);
    }

    return false;
}
