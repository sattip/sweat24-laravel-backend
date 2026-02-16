<?php

namespace Tests\Unit\Exceptions;

use App\Exceptions\BusinessValidationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Tests\TestCase;

class BusinessValidationExceptionTest extends TestCase
{
    public function test_exception_stores_message(): void
    {
        $exception = new BusinessValidationException('Test error message');

        $this->assertEquals('Test error message', $exception->getMessage());
    }

    public function test_exception_stores_error_code(): void
    {
        $exception = new BusinessValidationException('Test error', 'ERR_001');

        $this->assertEquals('ERR_001', $exception->getErrorCode());
    }

    public function test_exception_stores_null_error_code_by_default(): void
    {
        $exception = new BusinessValidationException('Test error');

        $this->assertNull($exception->getErrorCode());
    }

    public function test_render_returns_json_response(): void
    {
        $exception = new BusinessValidationException('Test error', 'ERR_001');
        $request = Request::create('/test', 'POST');

        $response = $exception->render($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
    }

    public function test_render_returns_422_status_code(): void
    {
        $exception = new BusinessValidationException('Test error');
        $request = Request::create('/test', 'POST');

        $response = $exception->render($request);

        $this->assertEquals(422, $response->getStatusCode());
    }

    public function test_render_returns_proper_json_structure(): void
    {
        $exception = new BusinessValidationException('Booking is not allowed', 'BOOKING_DENIED');
        $request = Request::create('/test', 'POST');

        $response = $exception->render($request);
        $data = $response->getData(true);

        $this->assertArrayHasKey('success', $data);
        $this->assertArrayHasKey('message', $data);
        $this->assertArrayHasKey('error_code', $data);

        $this->assertFalse($data['success']);
        $this->assertEquals('Booking is not allowed', $data['message']);
        $this->assertEquals('BOOKING_DENIED', $data['error_code']);
    }

    public function test_render_with_null_error_code(): void
    {
        $exception = new BusinessValidationException('Validation failed');
        $request = Request::create('/test', 'POST');

        $response = $exception->render($request);
        $data = $response->getData(true);

        $this->assertFalse($data['success']);
        $this->assertEquals('Validation failed', $data['message']);
        $this->assertNull($data['error_code']);
    }

    public function test_exception_can_be_thrown_and_caught(): void
    {
        $this->expectException(BusinessValidationException::class);
        $this->expectExceptionMessage('Cannot complete this action');

        throw new BusinessValidationException('Cannot complete this action', 'ACTION_DENIED');
    }

    public function test_render_preserves_special_characters_in_message(): void
    {
        $exception = new BusinessValidationException('Error: <script>alert("xss")</script>');
        $request = Request::create('/test', 'POST');

        $response = $exception->render($request);
        $data = $response->getData(true);

        $this->assertEquals('Error: <script>alert("xss")</script>', $data['message']);
    }

    public function test_render_handles_unicode_message(): void
    {
        $exception = new BusinessValidationException('Σφάλμα: Η κράτηση δεν είναι δυνατή');
        $request = Request::create('/test', 'POST');

        $response = $exception->render($request);
        $data = $response->getData(true);

        $this->assertEquals('Σφάλμα: Η κράτηση δεν είναι δυνατή', $data['message']);
    }
}
