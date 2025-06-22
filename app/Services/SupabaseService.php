<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class SupabaseService
{
    protected $url;
    protected $key;

    public function __construct()
    {
        $this->url = config('services.supabase.url');
        $this->key = config('services.supabase.key');
    }

    private function headers()
    {
        return [
            'apikey' => $this->key,
            'Authorization' => 'Bearer ' . $this->key,
            'Content-Type' => 'application/json',
            'Prefer' => 'return=representation'
        ];
    }

    public function select($table, $columns = '*', $filters = [])
    {
        $query = array_merge(['select' => $columns], $filters);

        $response = Http::withHeaders($this->headers())
            ->get("{$this->url}/rest/v1/{$table}", $query);

        return $response->json();
    }

    public function insert($table, $data)
    {
        $response = Http::withHeaders($this->headers())
            ->post("{$this->url}/rest/v1/{$table}", $data);

        return $response->json();
    }

    public function insertBatch($table, $dataArray)
    {
        $response = Http::withHeaders($this->headers())
            ->post("{$this->url}/rest/v1/{$table}", $dataArray);

        return $response->json();
    }

    public function update($table, $data, $filters)
    {
        $queryString = http_build_query($filters);

        $response = Http::withHeaders($this->headers())
            ->patch("{$this->url}/rest/v1/{$table}?{$queryString}", $data);

        return $response->json();
    }

    public function delete($table, $filters)
    {
        $queryString = http_build_query($filters);

        $response = Http::withHeaders($this->headers())
            ->delete("{$this->url}/rest/v1/{$table}?{$queryString}");

        return $response->json();
    }

    // Helper methods for common filters
    public function eq($column, $value)
    {
        return [$column => "eq.{$value}"];
    }

    public function like($column, $pattern)
    {
        return [$column => "like.{$pattern}"];
    }

    public function gt($column, $value)
    {
        return [$column => "gt.{$value}"];
    }

    public function lt($column, $value)
    {
        return [$column => "lt.{$value}"];
    }
}
