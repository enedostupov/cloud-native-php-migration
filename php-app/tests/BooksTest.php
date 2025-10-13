<?php
use PHPUnit\Framework\TestCase;

class BooksTest extends TestCase
{
    private $baseUrl;

    protected function setUp(): void
    {
        $this->baseUrl = getenv('API_URL') ?: 'http://localhost';
    }

    public function testCreateBook()
    {
        $data = ['title' => 'Test Book', 'author' => 'Tester'];
        $response = $this->post('/api/items', $data);
        $this->assertArrayHasKey('id', $response);
    }

    public function testListBooks()
    {
        $response = $this->get('/api/items');
        $this->assertIsArray($response);
    }

    public function testGetBook()
    {
        $create = $this->post('/api/items', ['title' => 'FetchMe', 'author' => 'Author']);
        $book = $this->get('/api/items/' . $create['id']);
        $this->assertEquals('FetchMe', $book['title']);
    }

    public function testUpdateBook()
    {
        $create = $this->post('/api/items', ['title' => 'Old', 'author' => 'A']);
        $update = $this->put('/api/items/' . $create['id'], ['title' => 'New', 'author' => 'B']);
        $this->assertTrue($update['ok']);
    }

    public function testDeleteBook()
    {
        $create = $this->post('/api/items', ['title' => 'DeleteMe', 'author' => 'X']);
        $delete = $this->delete('/api/items/' . $create['id']);
        $this->assertTrue($delete['ok']);
    }

    // TODO: add validation tests

    public function testGetNonExistentBook()
    {
        $response = $this->get('/api/items/999999');
        $this->assertArrayHasKey('error', $response);
        $this->assertEquals('not_found', $response['error']);
    }

    public function testUpdateNonExistentBook()
    {
        $response = $this->put('/api/items/999999', ['title' => 'New']);
        $this->assertArrayHasKey('error', $response);
        $this->assertEquals('not_found', $response['error']);
    }

    public function testDeleteNonExistentBook()
    {
        $response = $this->delete('/api/items/999999');
        $this->assertArrayHasKey('error', $response);
        $this->assertEquals('not_found', $response['error']);
    }

    public function testHealthEndpoint()
    {
        $response = $this->get('/health');
        $this->assertEquals('ok', $response['status']);
        $this->assertEquals('connected', $response['db']);
    }

    private function get($path)
    {
        $ctx = stream_context_create([
            'http' => ['ignore_errors' => true]
        ]);
        $res = file_get_contents($this->baseUrl . $path, false, $ctx);
        return json_decode($res, true);
    }

    private function post($path, $data)
    {
        $ctx = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => 'Content-Type: application/json',
                'content' => json_encode($data),
                'ignore_errors' => true
            ]
        ]);
        $res = file_get_contents($this->baseUrl . $path, false, $ctx);
        return json_decode($res, true);
    }

    private function put($path, $data)
    {
        $ctx = stream_context_create([
            'http' => [
                'method' => 'PUT',
                'header' => 'Content-Type: application/json',
                'content' => json_encode($data),
                'ignore_errors' => true
            ]
        ]);
        $res = file_get_contents($this->baseUrl . $path, false, $ctx);
        return json_decode($res, true);
    }

    private function delete($path)
    {
        $ctx = stream_context_create([
            'http' => [
                'method' => 'DELETE',
                'ignore_errors' => true
            ]
        ]);
        $res = file_get_contents($this->baseUrl . $path, false, $ctx);
        return json_decode($res, true);
    }
}
