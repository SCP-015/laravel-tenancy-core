<?php

namespace Tests\Feature\Central;

use App\Exceptions\InertiaExceptionHandler;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Tests\TestCase;

/**
 * Test untuk InertiaExceptionHandler
 * 
 * Coverage target:
 * - Lines 62-66: getDefaultMessage() match statement with all cases
 */
class InertiaExceptionHandlerTest extends TestCase
{
    /**
     * Test: 404 exception renders Inertia error page with correct message
     */
    public function test_404_exception_renders_inertia_error_page(): void
    {
        // ARRANGE
        $request = Request::create('/some-page', 'GET');
        $exception = new NotFoundHttpException('Custom 404 message');

        // ACT
        $response = $this->invokeRenderMethod($exception, $request);

        // ASSERT
        $this->assertNotNull($response);
        $this->assertEquals(404, $response->getStatusCode());
        
        // Verify response has content
        $content = $response->getContent();
        $this->assertNotEmpty($content);
    }

    /**
     * Test: 404 exception uses default message when no message provided
     * 
     * Cover line 63: 404 => 'Page not found.'
     */
    public function test_404_exception_uses_default_message_when_empty(): void
    {
        // ARRANGE
        $request = Request::create('/missing-page', 'GET');
        $exception = new NotFoundHttpException(); // No custom message

        // ACT
        $response = $this->invokeRenderMethod($exception, $request);

        // ASSERT
        $this->assertNotNull($response);
        $this->assertEquals(404, $response->getStatusCode());
        
        // Check that default message is used
        $content = $response->getContent();
        $this->assertStringContainsString('Page not found', $content);
    }

    /**
     * Test: 500 exception renders Inertia error page
     * 
     * Cover line 64: 500 => 'Internal server error.'
     */
    public function test_500_exception_renders_inertia_error_page(): void
    {
        // ARRANGE
        $request = Request::create('/error-page', 'GET');
        $exception = new HttpException(500, 'Something went wrong');

        // ACT
        $response = $this->invokeRenderMethod($exception, $request);

        // ASSERT
        $this->assertNotNull($response);
        $this->assertEquals(500, $response->getStatusCode());
        
        $content = $response->getContent();
        $this->assertNotEmpty($content);
        $this->assertStringContainsString('Something went wrong', $content);
    }

    /**
     * Test: 500 exception uses default message when no message provided
     */
    public function test_500_exception_uses_default_message_when_empty(): void
    {
        // ARRANGE
        $request = Request::create('/server-error', 'GET');
        $exception = new HttpException(500, ''); // Empty message

        // ACT
        $response = $this->invokeRenderMethod($exception, $request);

        // ASSERT
        $this->assertNotNull($response);
        $this->assertEquals(500, $response->getStatusCode());
        
        $content = $response->getContent();
        $this->assertStringContainsString('Internal server error', $content);
    }

    /**
     * Test: Other HTTP exceptions return null (not handled)
     * 
     * This tests that unhandled status codes return null
     */
    public function test_unhandled_status_codes_return_null(): void
    {
        // ARRANGE
        $request = Request::create('/forbidden', 'GET');
        $exception403 = new HttpException(403, 'Forbidden');
        $exception401 = new HttpException(401, 'Unauthorized');

        // ACT
        $response403 = $this->invokeRenderMethod($exception403, $request);
        $response401 = $this->invokeRenderMethod($exception401, $request);

        // ASSERT - Unhandled exceptions should return null
        $this->assertNull($response403);
        $this->assertNull($response401);
    }

    /**
     * Test: getDefaultMessage returns correct message for each status
     * 
     * Cover lines 62-66: Complete match statement including default case
     */
    public function test_get_default_message_returns_correct_messages(): void
    {
        // Use reflection to test private method
        $method = new \ReflectionMethod(InertiaExceptionHandler::class, 'getDefaultMessage');
        $method->setAccessible(true);

        // ACT & ASSERT
        // Cover line 63: 404 case
        $message404 = $method->invoke(null, 404);
        $this->assertEquals('Page not found.', $message404);

        // Cover line 64: 500 case
        $message500 = $method->invoke(null, 500);
        $this->assertEquals('Internal server error.', $message500);

        // Cover line 65: default case (any other status code)
        $messageDefault = $method->invoke(null, 418); // I'm a teapot
        $this->assertEquals('An error occurred.', $messageDefault);
        
        $messageDefault2 = $method->invoke(null, 503);
        $this->assertEquals('An error occurred.', $messageDefault2);
    }

    /**
     * Test: Custom message is used when provided
     */
    public function test_custom_message_is_used_when_provided(): void
    {
        // ARRANGE
        $request = Request::create('/test', 'GET');
        $customMessage = 'This is a custom error message';
        $exception = new NotFoundHttpException($customMessage);

        // ACT
        $response = $this->invokeRenderMethod($exception, $request);

        // ASSERT
        $this->assertNotNull($response);
        $content = $response->getContent();
        $this->assertStringContainsString($customMessage, $content);
    }

    /**
     * Helper method to invoke renderInertiaError private method
     */
    private function invokeRenderMethod(HttpException $exception, Request $request)
    {
        $method = new \ReflectionMethod(InertiaExceptionHandler::class, 'renderInertiaError');
        $method->setAccessible(true);
        
        return $method->invoke(null, $exception, $request);
    }
}
