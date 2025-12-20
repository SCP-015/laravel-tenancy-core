<?php

namespace Tests\Feature\Central;

use App\Helpers\ApiResponse;
use Tests\TestCase;

/**
 * Test untuk ApiResponse Helper
 * 
 * Coverage target:
 * - Line 41: $response['errors'] = $errors;
 */
class ApiResponseTest extends TestCase
{
    /**
     * Test: success() mengembalikan response JSON yang benar tanpa data
     */
    public function test_success_returns_correct_json_without_data(): void
    {
        // ACT
        $response = ApiResponse::success();
        $content = json_decode($response->getContent(), true);

        // ASSERT
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertArrayHasKey('code', $content);
        $this->assertArrayHasKey('title', $content);
        $this->assertArrayHasKey('message', $content);
        $this->assertEquals(200, $content['code']);
        $this->assertEquals('Success', $content['title']);
    }

    /**
     * Test: success() mengembalikan response JSON dengan data
     */
    public function test_success_returns_correct_json_with_data(): void
    {
        // ARRANGE
        $data = ['id' => 1, 'name' => 'Test'];

        // ACT
        $response = ApiResponse::success($data, 'Data retrieved successfully');
        $content = json_decode($response->getContent(), true);

        // ASSERT
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals(200, $content['code']);
        $this->assertEquals('Success', $content['title']);
        $this->assertEquals('Data retrieved successfully', $content['message']);
        $this->assertEquals($data, $content['data']);
    }

    /**
     * Test: error() mengembalikan response JSON tanpa errors detail
     */
    public function test_error_returns_correct_json_without_errors(): void
    {
        // ACT
        $response = ApiResponse::error('Something went wrong');
        $content = json_decode($response->getContent(), true);

        // ASSERT
        $this->assertEquals(400, $response->getStatusCode());
        $this->assertEquals(400, $content['code']);
        $this->assertEquals('Error', $content['title']);
        $this->assertEquals('Something went wrong', $content['message']);
        $this->assertArrayNotHasKey('errors', $content);
    }

    /**
     * Test: error() mengembalikan response JSON dengan errors detail
     * 
     * Cover line 41: $response['errors'] = $errors;
     */
    public function test_error_returns_correct_json_with_errors(): void
    {
        // ARRANGE
        $errors = [
            'email' => ['Email is required'],
            'password' => ['Password must be at least 8 characters'],
        ];

        // ACT
        $response = ApiResponse::error('Validation failed', $errors, 422, 'Validation Error');
        $content = json_decode($response->getContent(), true);

        // ASSERT
        $this->assertEquals(422, $response->getStatusCode());
        $this->assertEquals(422, $content['code']);
        $this->assertEquals('Validation Error', $content['title']);
        $this->assertEquals('Validation failed', $content['message']);
        $this->assertEquals($errors, $content['errors']);
    }

    /**
     * Test: success() dengan custom code dan title
     */
    public function test_success_with_custom_code_and_title(): void
    {
        // ACT
        $response = ApiResponse::success(['created' => true], 'Resource created', 201, 'Created');
        $content = json_decode($response->getContent(), true);

        // ASSERT
        $this->assertEquals(201, $response->getStatusCode());
        $this->assertEquals(201, $content['code']);
        $this->assertEquals('Created', $content['title']);
        $this->assertEquals('Resource created', $content['message']);
    }

    /**
     * Test: error() dengan custom code
     */
    public function test_error_with_custom_code(): void
    {
        // ACT
        $response = ApiResponse::error('Not found', null, 404, 'Not Found');
        $content = json_decode($response->getContent(), true);

        // ASSERT
        $this->assertEquals(404, $response->getStatusCode());
        $this->assertEquals(404, $content['code']);
        $this->assertEquals('Not Found', $content['title']);
        $this->assertEquals('Not found', $content['message']);
    }
}
