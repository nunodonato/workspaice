<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Project extends Model
{
    use HasFactory;

    public $guarded = [];

    public $casts = [
        'files' => 'array',
    ];

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    public function calls(): HasMany
    {
        return $this->hasMany(Call::class);
    }

    public function snapshots(): HasMany
    {
        return $this->hasMany(Snapshot::class);
    }

    public function updateFileMap(): void
    {
        $result = $this->refreshFileMap($this->full_path, false);
        if (strlen($result) > 1024 * 4) {
            $this->refreshFileMap($this->full_path, true);
        }
    }

    private function refreshFileMap(string $path, $onlyFolders = false): string
    {
        $path = rtrim($path, DIRECTORY_SEPARATOR);
        $pathLength = strlen($path) + 1; // +1 for the trailing slash
        $result = '';
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $file) {
            if ($file->isDir() && $onlyFolders) {
                $relativePath = substr($file->getPathname(), $pathLength);
                if (strpos($relativePath, '.') !== 0) {
                    $result .= $relativePath.PHP_EOL;
                }
            }

            if ($file->isFile() && ! $onlyFolders) {
                $relativePath = substr($file->getPathname(), $pathLength);
                // Skip files in hidden folders
                if (strpos($relativePath, '.') !== 0) {
                    $result .= $relativePath.PHP_EOL;
                }
            }
        }

        if ($onlyFolders) {
            $filemapFile = $this->full_path.DIRECTORY_SEPARATOR.'.workspaice/filemap';
            unlink($filemapFile);
            $filemapFile = $this->full_path.DIRECTORY_SEPARATOR.'.workspaice/dirmap';
        } else {
            $filemapFile = $this->full_path.DIRECTORY_SEPARATOR.'.workspaice/filemap';
        }

        file_put_contents($filemapFile, $result);

        return $result;
    }
}
