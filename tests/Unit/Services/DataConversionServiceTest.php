<?php

namespace Tests\Unit\Services;

use App\Services\DataConversionService;
use PHPUnit\Framework\TestCase;

class DataConversionServiceTest extends TestCase
{
    /**
     * Test: toBool converts boolean values correctly
     */
    public function test_to_bool_with_boolean_values(): void
    {
        $this->assertTrue(DataConversionService::toBool(true));
        $this->assertFalse(DataConversionService::toBool(false));
    }

    /**
     * Test: toBool converts string values correctly
     */
    public function test_to_bool_with_string_values(): void
    {
        $this->assertTrue(DataConversionService::toBool('1'));
        $this->assertTrue(DataConversionService::toBool('true'));
        $this->assertTrue(DataConversionService::toBool('TRUE'));
        $this->assertTrue(DataConversionService::toBool('True'));
        
        $this->assertFalse(DataConversionService::toBool('0'));
        $this->assertFalse(DataConversionService::toBool('false'));
        $this->assertFalse(DataConversionService::toBool('invalid'));
    }

    /**
     * Test: toBool converts numeric values correctly
     */
    public function test_to_bool_with_numeric_values(): void
    {
        $this->assertTrue(DataConversionService::toBool(1));
        $this->assertFalse(DataConversionService::toBool(0));
        $this->assertFalse(DataConversionService::toBool(2));
        $this->assertFalse(DataConversionService::toBool(-1));
    }

    /**
     * Test: toBool returns default value for invalid input
     */
    public function test_to_bool_returns_default_value(): void
    {
        $this->assertFalse(DataConversionService::toBool(null));
        $this->assertFalse(DataConversionService::toBool([]));
        $this->assertFalse(DataConversionService::toBool(new \stdClass()));
        
        $this->assertTrue(DataConversionService::toBool(null, true));
        $this->assertTrue(DataConversionService::toBool([], true));
    }

    /**
     * Test: arrayToBool converts array key to boolean
     */
    public function test_array_to_bool(): void
    {
        $input = ['is_active' => '1', 'is_deleted' => '0'];
        
        $this->assertTrue(DataConversionService::arrayToBool($input, 'is_active'));
        $this->assertFalse(DataConversionService::arrayToBool($input, 'is_deleted'));
        $this->assertFalse(DataConversionService::arrayToBool($input, 'non_existent'));
        $this->assertTrue(DataConversionService::arrayToBool($input, 'non_existent', true));
    }

    /**
     * Test: arrayMultipleToBool converts multiple keys at once
     */
    public function test_array_multiple_to_bool(): void
    {
        $input = ['is_active' => '1', 'is_verified' => 'true', 'is_deleted' => 0];
        
        $result = DataConversionService::arrayMultipleToBool($input, [
            'is_active' => false,
            'is_verified' => false,
            'is_deleted' => false,
            'is_admin' => true,
        ]);
        
        $this->assertTrue($result['is_active']);
        $this->assertTrue($result['is_verified']);
        $this->assertFalse($result['is_deleted']);
        $this->assertTrue($result['is_admin']); // Default value
    }

    /**
     * Test: toInt converts various types to integer
     */
    public function test_to_int_with_various_types(): void
    {
        $this->assertEquals(42, DataConversionService::toInt(42));
        $this->assertEquals(42, DataConversionService::toInt(42.7));
        $this->assertEquals(42, DataConversionService::toInt('42'));
        $this->assertEquals(0, DataConversionService::toInt('invalid'));
        $this->assertEquals(99, DataConversionService::toInt('invalid', 99));
    }

    /**
     * Test: toString converts various types to string
     */
    public function test_to_string_with_various_types(): void
    {
        $this->assertEquals('hello', DataConversionService::toString('hello'));
        $this->assertEquals('hello', DataConversionService::toString('  hello  '));
        $this->assertEquals('42', DataConversionService::toString(42));
        $this->assertEquals('1', DataConversionService::toString(true));
        // false is not numeric, so returns default value (empty string)
        $this->assertEquals('', DataConversionService::toString(false));
        $this->assertEquals('', DataConversionService::toString(null));
        $this->assertEquals('default', DataConversionService::toString(null, 'default'));
    }
}
