<?php

namespace App\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Collection;

class JsonStorageService
{
    private string $basePath;

    public function __construct()
    {
        $this->basePath = storage_path('app/data');
        $this->ensureDirectoryExists();
    }

    private function ensureDirectoryExists(): void
    {
        if (!File::exists($this->basePath)) {
            File::makeDirectory($this->basePath, 0755, true);
        }
    }

    public function getPath(string $filename): string
    {
        return $this->basePath . '/' . $filename . '.json';
    }

    public function get(string $filename): Collection
    {
        $path = $this->getPath($filename);
        
        if (!File::exists($path)) {
            return collect([]);
        }

        $contents = File::get($path);
        $data = json_decode($contents, true);
        
        return collect($data ?? []);
    }

    public function put(string $filename, Collection $data): bool
    {
        $path = $this->getPath($filename);
        // Ensure we get a sequential array, not associative with numeric keys
        $array = array_values($data->toArray());
        $json = json_encode($array, JSON_PRETTY_PRINT);

        return File::put($path, $json) !== false;
    }

    public function find(string $filename, int $id): ?array
    {
        return $this->get($filename)->firstWhere('id', $id);
    }

    public function create(string $filename, array $data): array
    {
        $collection = $this->get($filename);
        
        // Generate new ID
        $maxId = $collection->max('id') ?? 0;
        $data['id'] = $maxId + 1;
        $data['created_at'] = now()->toISOString();
        $data['updated_at'] = now()->toISOString();
        
        $collection->push($data);
        $this->put($filename, $collection);
        
        return $data;
    }

    public function update(string $filename, int $id, array $data): ?array
    {
        $collection = $this->get($filename);
        $index = $collection->search(fn($item) => $item['id'] === $id);
        
        if ($index === false) {
            return null;
        }
        
        $item = $collection[$index];
        $updated = array_merge($item, $data, ['updated_at' => now()->toISOString()]);
        
        $collection[$index] = $updated;
        $this->put($filename, $collection);
        
        return $updated;
    }

    public function delete(string $filename, int $id): bool
    {
        $collection = $this->get($filename);
        $filtered = $collection->reject(fn($item) => $item['id'] === $id);
        
        if ($collection->count() === $filtered->count()) {
            return false; // Item not found
        }
        
        $this->put($filename, $filtered);
        return true;
    }
}
