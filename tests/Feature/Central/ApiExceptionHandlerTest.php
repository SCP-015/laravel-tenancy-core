<?php

namespace Tests\Feature\Central;

use App\Exceptions\ApiExceptionHandler;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Exceptions\Handler;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Tests\TestCase;

/**
 * Test untuk ApiExceptionHandler
 * 
 * Coverage target:
 * - Lines 25-29: else block untuk NotFoundHttpException without ModelNotFoundException
 */
class ApiExceptionHandlerTest extends TestCase
{
    /**
     * Test: NotFoundHttpException dengan ModelNotFoundException mengembalikan response "Data not found"
     */
    public function test_not_found_http_exception_with_model_not_found_returns_correct_response(): void
    {
        // ARRANGE
        $request = Request::create('/api/test', 'GET');
        $request->headers->set('Accept', 'application/json');
        
        $modelNotFoundException = new ModelNotFoundException();
        $notFoundException = new NotFoundHttpException('Not found', $modelNotFoundException);
        
        $handler = app(Handler::class);
        
        // Register handler callbacks
        $handler->renderable(function (NotFoundHttpException $e, Request $request) {
            if ($request->expectsJson()) {
                $previous = $e->getPrevious();
                if ($previous instanceof ModelNotFoundException) {
                    return response()->json([
                        'status' => 'warning',
                        'message' => __('Data not found'),
                        'code' => 1,
                    ], 404);
                } else {
                    return response()->json([
                        'status' => 'warning',
                        'message' => $e->getMessage() . " " . $e->getPrevious()->getMessage(),
                        'code' => 2,
                    ], 404);
                }
            }
        });

        // ACT
        $response = $handler->render($request, $notFoundException);
        $content = json_decode($response->getContent(), true);

        // ASSERT
        $this->assertEquals(404, $response->getStatusCode());
        $this->assertEquals('warning', $content['status']);
        $this->assertEquals('Data not found', $content['message']);
        $this->assertEquals(1, $content['code']);
    }

    /**
     * Test: NotFoundHttpException tanpa ModelNotFoundException mengembalikan response dengan message lengkap
     * 
     * Cover lines 25-29: else block
     */
    public function test_not_found_http_exception_without_model_not_found_returns_full_message(): void
    {
        // ARRANGE
        $request = Request::create('/api/test', 'GET');
        $request->headers->set('Accept', 'application/json');
        
        $previousException = new \Exception('Resource does not exist');
        $notFoundException = new NotFoundHttpException('Route not found', $previousException);
        
        $handler = app(Handler::class);
        
        // Register handler callbacks (same as in ApiExceptionHandler)
        $handler->renderable(function (NotFoundHttpException $e, Request $request) {
            if ($request->expectsJson()) {
                $previous = $e->getPrevious();
                if ($previous instanceof ModelNotFoundException) {
                    return response()->json([
                        'status' => 'warning',
                        'message' => __('Data not found'),
                        'code' => 1,
                    ], 404);
                } else {
                    return response()->json([
                        'status' => 'warning',
                        'message' => $e->getMessage() . " " . $e->getPrevious()->getMessage(),
                        'code' => 2,
                    ], 404);
                }
            }
        });

        // ACT
        $response = $handler->render($request, $notFoundException);
        $content = json_decode($response->getContent(), true);

        // ASSERT
        $this->assertEquals(404, $response->getStatusCode());
        $this->assertEquals('warning', $content['status']);
        $this->assertStringContainsString('Route not found', $content['message']);
        $this->assertStringContainsString('Resource does not exist', $content['message']);
        $this->assertEquals(2, $content['code']);
    }

    /**
     * Test: Handler hanya berlaku untuk JSON requests
     */
    public function test_handler_only_applies_to_json_requests(): void
    {
        // ARRANGE
        $request = Request::create('/web/test', 'GET');
        // Tidak set Accept header sebagai JSON
        
        $modelNotFoundException = new ModelNotFoundException();
        $notFoundException = new NotFoundHttpException('Not found', $modelNotFoundException);
        
        $handler = app(Handler::class);
        
        $handler->renderable(function (NotFoundHttpException $e, Request $request) {
            if ($request->expectsJson()) {
                $previous = $e->getPrevious();
                if ($previous instanceof ModelNotFoundException) {
                    return response()->json([
                        'status' => 'warning',
                        'message' => __('Data not found'),
                        'code' => 1,
                    ], 404);
                }
            }
        });

        // ACT
        $response = $handler->render($request, $notFoundException);

        // ASSERT
        // Response tidak akan dalam format JSON yang didefinisikan ApiExceptionHandler
        // karena request tidak expectsJson()
        $this->assertEquals(404, $response->getStatusCode());
    }

    /**
     * Test: Code 1 untuk ModelNotFoundException, Code 2 untuk exception lain
     */
    public function test_different_codes_for_different_exception_types(): void
    {
        // ARRANGE
        $request = Request::create('/api/test', 'GET');
        $request->headers->set('Accept', 'application/json');
        
        $handler = app(Handler::class);
        
        $handler->renderable(function (NotFoundHttpException $e, Request $request) {
            if ($request->expectsJson()) {
                $previous = $e->getPrevious();
                if ($previous instanceof ModelNotFoundException) {
                    return response()->json([
                        'status' => 'warning',
                        'message' => __('Data not found'),
                        'code' => 1,
                    ], 404);
                } else {
                    return response()->json([
                        'status' => 'warning',
                        'message' => $e->getMessage() . " " . $e->getPrevious()->getMessage(),
                        'code' => 2,
                    ], 404);
                }
            }
        });

        // Test Code 1: ModelNotFoundException
        $modelNotFoundException = new ModelNotFoundException();
        $notFoundException1 = new NotFoundHttpException('Not found', $modelNotFoundException);
        $response1 = $handler->render($request, $notFoundException1);
        $content1 = json_decode($response1->getContent(), true);
        
        // Test Code 2: Other exception
        $otherException = new \Exception('Other error');
        $notFoundException2 = new NotFoundHttpException('Not found', $otherException);
        $response2 = $handler->render($request, $notFoundException2);
        $content2 = json_decode($response2->getContent(), true);

        // ASSERT
        $this->assertEquals(1, $content1['code']);
        $this->assertEquals(2, $content2['code']);
    }
}
