<?php

namespace Tests\Feature\Central;

use App\Http\Requests\TenantRequest;
use App\Models\CompanyCategory;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

/**
 * Test untuk TenantRequest validation
 */
class TenantRequestTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    /**
     * Test: Validasi berhasil dengan data yang valid
     */
    public function test_validation_passes_with_valid_data(): void
    {
        // ARRANGE
        $category = CompanyCategory::factory()->create();

        $data = [
            'name' => 'PT Test Company',
            'code' => 'TESTCO',
            'theme_color' => '#FF5733',
            'company_category_id' => $category->id,
            'company_values' => 'Innovation, Integrity, Excellence',
            'employee_range_start' => 10,
            'employee_range_end' => 50,
        ];

        $request = new TenantRequest();

        // ACT
        $validator = Validator::make($data, $request->rules());

        // ASSERT
        $this->assertFalse($validator->fails());
    }

    /**
     * Test: Validasi gagal jika name kurang dari 3 karakter
     */
    public function test_validation_fails_when_name_is_too_short(): void
    {
        // ARRANGE
        $data = [
            'name' => 'AB',
            'code' => 'TESTCO',
        ];

        $request = new TenantRequest();

        // ACT
        $validator = Validator::make($data, $request->rules());

        // ASSERT
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('name', $validator->errors()->toArray());
    }

    /**
     * Test: Validasi gagal jika code kurang dari 6 karakter
     */
    public function test_validation_fails_when_code_is_too_short(): void
    {
        // ARRANGE
        $data = [
            'name' => 'PT Test',
            'code' => 'TEST',
        ];

        $request = new TenantRequest();

        // ACT
        $validator = Validator::make($data, $request->rules());

        // ASSERT
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('code', $validator->errors()->toArray());
    }

    /**
     * Test: Validasi gagal jika theme_color format invalid
     */
    public function test_validation_fails_when_theme_color_format_is_invalid(): void
    {
        // ARRANGE
        $data = [
            'name' => 'PT Test Company',
            'code' => 'TESTCO',
            'theme_color' => 'FF5733', // Missing #
        ];

        $request = new TenantRequest();

        // ACT
        $validator = Validator::make($data, $request->rules());

        // ASSERT
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('theme_color', $validator->errors()->toArray());
    }

    /**
     * Test: Validasi berhasil dengan header_image yang valid
     */
    public function test_validation_passes_with_valid_header_image(): void
    {
        // ARRANGE
        $data = [
            'name' => 'PT Test Company',
            'code' => 'TESTCO',
            'header_image' => UploadedFile::fake()->image('header.jpg', 1000, 500)->size(1024),
        ];

        $request = new TenantRequest();

        // ACT
        $validator = Validator::make($data, $request->rules());

        // ASSERT
        $this->assertFalse($validator->fails());
    }

    /**
     * Test: Validasi gagal jika header_image melebihi ukuran maksimal
     */
    public function test_validation_fails_when_header_image_exceeds_max_size(): void
    {
        // ARRANGE
        $data = [
            'name' => 'PT Test Company',
            'code' => 'TESTCO',
            'header_image' => UploadedFile::fake()->image('header.jpg')->size(3000), // 3MB
        ];

        $request = new TenantRequest();

        // ACT
        $validator = Validator::make($data, $request->rules());

        // ASSERT
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('header_image', $validator->errors()->toArray());
    }

    /**
     * Test: Validasi gagal jika profile_image format invalid
     */
    public function test_validation_fails_when_profile_image_format_is_invalid(): void
    {
        // ARRANGE
        $data = [
            'name' => 'PT Test Company',
            'code' => 'TESTCO',
            'profile_image' => UploadedFile::fake()->create('profile.pdf', 1000),
        ];

        $request = new TenantRequest();

        // ACT
        $validator = Validator::make($data, $request->rules());

        // ASSERT
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('profile_image', $validator->errors()->toArray());
    }

    /**
     * Test: Validasi gagal jika company_category_id tidak exists
     */
    public function test_validation_fails_when_company_category_id_does_not_exist(): void
    {
        // ARRANGE
        $data = [
            'name' => 'PT Test Company',
            'code' => 'TESTCO',
            'company_category_id' => 99999,
        ];

        $request = new TenantRequest();

        // ACT
        $validator = Validator::make($data, $request->rules());

        // ASSERT
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('company_category_id', $validator->errors()->toArray());
    }

    /**
     * Test: Validasi gagal jika employee_range_end kurang dari employee_range_start
     */
    public function test_validation_fails_when_employee_range_end_less_than_start(): void
    {
        // ARRANGE
        $data = [
            'name' => 'PT Test Company',
            'code' => 'TESTCO',
            'employee_range_start' => 50,
            'employee_range_end' => 10,
        ];

        $request = new TenantRequest();

        // ACT
        $validator = Validator::make($data, $request->rules());

        // ASSERT
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('employee_range_end', $validator->errors()->toArray());
    }

    /**
     * Test: Validasi berhasil ketika social links berisi URL yang valid
     */
    public function test_validation_passes_with_valid_social_links(): void
    {
        // ARRANGE
        $data = [
            'name' => 'PT Test Company',
            'code' => 'TESTCO',
            'linkedin' => 'https://www.linkedin.com/company/test-company',
            'instagram' => 'https://www.instagram.com/testcompany',
            'website' => 'https://www.testcompany.co.id',
        ];

        $request = new TenantRequest();

        // ACT
        $validator = Validator::make($data, $request->rules());

        // ASSERT
        $this->assertFalse($validator->fails());
    }

    /**
     * Test: Validasi gagal ketika linkedin bukan URL yang valid
     */
    public function test_validation_fails_when_linkedin_is_not_valid_url(): void
    {
        // ARRANGE
        $data = [
            'name' => 'PT Test Company',
            'code' => 'TESTCO',
            'linkedin' => 'not-a-valid-url',
        ];

        $request = new TenantRequest();

        // ACT
        $validator = Validator::make($data, $request->rules());

        // ASSERT
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('linkedin', $validator->errors()->toArray());
    }

    /**
     * Test: Validasi gagal ketika instagram bukan URL yang valid
     */
    public function test_validation_fails_when_instagram_is_not_valid_url(): void
    {
        // ARRANGE
        $data = [
            'name' => 'PT Test Company',
            'code' => 'TESTCO',
            'instagram' => 'invalid-instagram-url',
        ];

        $request = new TenantRequest();

        // ACT
        $validator = Validator::make($data, $request->rules());

        // ASSERT
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('instagram', $validator->errors()->toArray());
    }

    /**
     * Test: Validasi gagal ketika website bukan URL yang valid
     */
    public function test_validation_fails_when_website_is_not_valid_url(): void
    {
        // ARRANGE
        $data = [
            'name' => 'PT Test Company',
            'code' => 'TESTCO',
            'website' => 'invalid-website-url',
        ];

        $request = new TenantRequest();

        // ACT
        $validator = Validator::make($data, $request->rules());

        // ASSERT
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('website', $validator->errors()->toArray());
    }

    /**
     * Test: Validasi tetap lolos ketika social links dikirim sebagai string kosong
     * (akan dinormalisasi menjadi null di prepareForValidation)
     */
    public function test_validation_passes_when_social_links_are_empty_strings(): void
    {
        // ARRANGE
        $data = [
            'name' => 'PT Test Company',
            'code' => 'TESTCO',
            'linkedin' => '',
            'instagram' => '',
            'website' => '',
        ];

        $request = new TenantRequest();

        // ACT
        $validator = Validator::make($data, $request->rules());

        // ASSERT
        $this->assertFalse($validator->fails());
    }

    /**
     * Test: Validasi gagal jika name sudah ada (duplicate)
     */
    public function test_validation_fails_when_name_is_duplicate(): void
    {
        // ARRANGE
        $existingTenant = Tenant::factory()->create([
            'name' => 'PT Existing Company',
        ]);

        $data = [
            'name' => 'PT Existing Company',
            'code' => 'NEWCODE',
        ];

        $request = new TenantRequest();

        // ACT
        $validator = Validator::make($data, $request->rules());

        // ASSERT
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('name', $validator->errors()->toArray());
    }

    /**
     * Test: Validasi gagal jika code sudah ada (duplicate)
     */
    public function test_validation_fails_when_code_is_duplicate(): void
    {
        // ARRANGE
        $existingTenant = Tenant::factory()->create([
            'code' => 'EXISTCO',
        ]);

        $data = [
            'name' => 'PT New Company',
            'code' => 'EXISTCO',
        ];

        $request = new TenantRequest();

        // ACT
        $validator = Validator::make($data, $request->rules());

        // ASSERT
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('code', $validator->errors()->toArray());
    }

    /**
     * Test: authorize() mengembalikan true
     */
    public function test_authorize_returns_true(): void
    {
        // ARRANGE
        $request = new TenantRequest();

        // ACT
        $authorized = $request->authorize();

        // ASSERT
        $this->assertTrue($authorized);
    }
}
