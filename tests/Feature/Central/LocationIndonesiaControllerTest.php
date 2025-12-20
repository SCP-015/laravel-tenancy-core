<?php

namespace Tests\Feature\Central;

use App\Models\Indonesia\City;
use App\Models\Indonesia\District;
use App\Models\Indonesia\Province;
use App\Models\Indonesia\Village;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Test untuk LocationIndonesiaController
 */
class LocationIndonesiaControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Bersihkan data Indonesia sebelum setiap test
        Village::query()->delete();
        District::query()->delete();
        City::query()->delete();
        Province::query()->delete();
    }

    /**
     * Test: Index provinces returns list of all provinces
     */
    public function test_index_provinces_returns_list_of_all_provinces(): void
    {
        // ARRANGE
        Province::create(['code' => '11', 'name' => 'Aceh']);
        Province::create(['code' => '12', 'name' => 'Sumatera Utara']);
        Province::create(['code' => '13', 'name' => 'Sumatera Barat']);

        // ACT
        $response = $this->getJson('/api/indonesia/provinces');

        // ASSERT
        $response->assertStatus(200);
        $response->assertJsonCount(3);
        $response->assertJsonFragment(['name' => 'Aceh']);
        $response->assertJsonFragment(['name' => 'Sumatera Utara']);
    }

    /**
     * Test: Index provinces returns empty array when no data
     */
    public function test_index_provinces_returns_empty_array_when_no_data(): void
    {
        // ACT
        $response = $this->getJson('/api/indonesia/provinces');

        // ASSERT
        $response->assertStatus(200);
        $response->assertJsonCount(0);
    }

    /**
     * Test: Index provinces returns ordered by name
     */
    public function test_index_provinces_returns_ordered_by_name(): void
    {
        // ARRANGE
        Province::create(['code' => '13', 'name' => 'Sumatera Barat']);
        Province::create(['code' => '11', 'name' => 'Aceh']);
        Province::create(['code' => '12', 'name' => 'Sumatera Utara']);

        // ACT
        $response = $this->getJson('/api/indonesia/provinces');

        // ASSERT
        $response->assertStatus(200);
        $data = $response->json();
        $this->assertEquals('Aceh', $data[0]['name']);
        $this->assertEquals('Sumatera Barat', $data[1]['name']);
        $this->assertEquals('Sumatera Utara', $data[2]['name']);
    }

    /**
     * Test: Show province returns province details by code
     */
    public function test_show_province_returns_province_details_by_code(): void
    {
        // ARRANGE
        $province = Province::create(['code' => '11', 'name' => 'Aceh']);

        // ACT
        $response = $this->getJson('/api/indonesia/provinces/11');

        // ASSERT
        $response->assertStatus(200);
        $response->assertJsonFragment([
            'code' => '11',
            'name' => 'Aceh',
        ]);
    }

    /**
     * Test: Show province returns 404 when province not found
     */
    public function test_show_province_returns_404_when_province_not_found(): void
    {
        // ACT
        $response = $this->getJson('/api/indonesia/provinces/99');

        // ASSERT
        $response->assertStatus(404);
        $response->assertJsonFragment(['message' => __('Province not found.')]);
    }

    /**
     * Test: Index cities returns list of cities for a province
     */
    public function test_index_cities_returns_list_of_cities_for_a_province(): void
    {
        // ARRANGE
        $province1 = Province::create(['code' => '31', 'name' => 'DKI Jakarta']);
        $province2 = Province::create(['code' => '32', 'name' => 'Jawa Barat']);
        City::create(['code' => '3101', 'province_code' => '31', 'name' => 'Kab. Kepulauan Seribu']);
        City::create(['code' => '3171', 'province_code' => '31', 'name' => 'Kota Jakarta Pusat']);
        City::create(['code' => '3201', 'province_code' => '32', 'name' => 'Kab. Bogor']);

        // ACT
        $response = $this->getJson('/api/indonesia/provinces/31/cities');

        // ASSERT
        $response->assertStatus(200);
        $response->assertJsonCount(2);
        $response->assertJsonFragment(['name' => 'Kab. Kepulauan Seribu']);
        $response->assertJsonFragment(['name' => 'Kota Jakarta Pusat']);
    }

    /**
     * Test: Index cities returns 404 when province not found
     */
    public function test_index_cities_returns_404_when_province_not_found(): void
    {
        // ACT
        $response = $this->getJson('/api/indonesia/provinces/99/cities');

        // ASSERT
        $response->assertStatus(404);
        $response->assertJsonFragment(['message' => __('Province not found for the given code.')]);
    }

    /**
     * Test: Index cities returns empty array when no cities
     */
    public function test_index_cities_returns_empty_array_when_no_cities(): void
    {
        // ARRANGE
        Province::create(['code' => '33', 'name' => 'Jawa Tengah']);

        // ACT
        $response = $this->getJson('/api/indonesia/provinces/33/cities');

        // ASSERT
        $response->assertStatus(200);
        $response->assertJsonCount(0);
    }

    /**
     * Test: Show city returns city details by code
     */
    public function test_show_city_returns_city_details_by_code(): void
    {
        // ARRANGE
        Province::create(['code' => '34', 'name' => 'DI Yogyakarta']);
        City::create(['code' => '3401', 'province_code' => '34', 'name' => 'Kab. Kulon Progo']);

        // ACT
        $response = $this->getJson('/api/indonesia/cities/3401');

        // ASSERT
        $response->assertStatus(200);
        $response->assertJsonFragment([
            'code' => '3401',
            'name' => 'Kab. Kulon Progo',
        ]);
    }

    /**
     * Test: Show city returns 404 when city not found
     */
    public function test_show_city_returns_404_when_city_not_found(): void
    {
        // ACT
        $response = $this->getJson('/api/indonesia/cities/9999');

        // ASSERT
        $response->assertStatus(404);
        $response->assertJsonFragment(['message' => __('City not found.')]);
    }

    /**
     * Test: Index districts returns list of districts for a city
     */
    public function test_index_districts_returns_list_of_districts_for_a_city(): void
    {
        // ARRANGE
        Province::create(['code' => '35', 'name' => 'Jawa Timur']);
        $city1 = City::create(['code' => '3501', 'province_code' => '35', 'name' => 'Kab. Pacitan']);
        $city2 = City::create(['code' => '3502', 'province_code' => '35', 'name' => 'Kab. Ponorogo']);
        District::create(['code' => '350101', 'city_code' => '3501', 'name' => 'Donorojo']);
        District::create(['code' => '350102', 'city_code' => '3501', 'name' => 'Punung']);
        District::create(['code' => '350201', 'city_code' => '3502', 'name' => 'Slahung']);

        // ACT
        $response = $this->getJson('/api/indonesia/cities/3501/districts');

        // ASSERT
        $response->assertStatus(200);
        $response->assertJsonCount(2);
        $response->assertJsonFragment(['name' => 'Donorojo']);
        $response->assertJsonFragment(['name' => 'Punung']);
    }

    /**
     * Test: Index districts returns 404 when city not found
     */
    public function test_index_districts_returns_404_when_city_not_found(): void
    {
        // ACT
        $response = $this->getJson('/api/indonesia/cities/9999/districts');

        // ASSERT
        $response->assertStatus(404);
        $response->assertJsonFragment(['message' => __('City not found for the given code.')]);
    }

    /**
     * Test: Index districts returns empty array when no districts
     */
    public function test_index_districts_returns_empty_array_when_no_districts(): void
    {
        // ARRANGE
        Province::create(['code' => '36', 'name' => 'Banten']);
        City::create(['code' => '3601', 'province_code' => '36', 'name' => 'Kab. Pandeglang']);

        // ACT
        $response = $this->getJson('/api/indonesia/cities/3601/districts');

        // ASSERT
        $response->assertStatus(200);
        $response->assertJsonCount(0);
    }

    /**
     * Test: Show district returns district details by code
     */
    public function test_show_district_returns_district_details_by_code(): void
    {
        // ARRANGE
        Province::create(['code' => '51', 'name' => 'Bali']);
        City::create(['code' => '5101', 'province_code' => '51', 'name' => 'Kab. Jembrana']);
        District::create(['code' => '510101', 'city_code' => '5101', 'name' => 'Negara']);

        // ACT
        $response = $this->getJson('/api/indonesia/districts/510101');

        // ASSERT
        $response->assertStatus(200);
        $response->assertJsonFragment([
            'code' => '510101',
            'name' => 'Negara',
        ]);
    }

    /**
     * Test: Show district returns 404 when district not found
     */
    public function test_show_district_returns_404_when_district_not_found(): void
    {
        // ACT
        $response = $this->getJson('/api/indonesia/districts/999999');

        // ASSERT
        $response->assertStatus(404);
        $response->assertJsonFragment(['message' => __('District not found.')]);
    }

    /**
     * Test: Index villages returns list of villages for a district
     */
    public function test_index_villages_returns_list_of_villages_for_a_district(): void
    {
        // ARRANGE
        Province::create(['code' => '52', 'name' => 'Nusa Tenggara Barat']);
        City::create(['code' => '5201', 'province_code' => '52', 'name' => 'Kab. Lombok Barat']);
        $district1 = District::create(['code' => '520101', 'city_code' => '5201', 'name' => 'Gerung']);
        $district2 = District::create(['code' => '520201', 'city_code' => '5201', 'name' => 'Labuapi']);
        Village::create(['code' => '52010101', 'district_code' => '520101', 'name' => 'Gerung Utara']);
        Village::create(['code' => '52010102', 'district_code' => '520101', 'name' => 'Gerung Selatan']);
        Village::create(['code' => '52020101', 'district_code' => '520201', 'name' => 'Other Village']);

        // ACT
        $response = $this->getJson('/api/indonesia/districts/520101/villages');

        // ASSERT
        $response->assertStatus(200);
        $response->assertJsonCount(2);
        $response->assertJsonFragment(['name' => 'Gerung Utara']);
        $response->assertJsonFragment(['name' => 'Gerung Selatan']);
    }

    /**
     * Test: Index villages returns 404 when district not found
     */
    public function test_index_villages_returns_404_when_district_not_found(): void
    {
        // ACT
        $response = $this->getJson('/api/indonesia/districts/999999/villages');

        // ASSERT
        $response->assertStatus(404);
        $response->assertJsonFragment(['message' => __('District not found for the given code.')]);
    }

    /**
     * Test: Index villages returns empty array when no villages
     */
    public function test_index_villages_returns_empty_array_when_no_villages(): void
    {
        // ARRANGE
        Province::create(['code' => '53', 'name' => 'Nusa Tenggara Timur']);
        City::create(['code' => '5301', 'province_code' => '53', 'name' => 'Kab. Sumba Barat']);
        District::create(['code' => '530101', 'city_code' => '5301', 'name' => 'Kodi']);

        // ACT
        $response = $this->getJson('/api/indonesia/districts/530101/villages');

        // ASSERT
        $response->assertStatus(200);
        $response->assertJsonCount(0);
    }

    /**
     * Test: Show village returns village details by code
     */
    public function test_show_village_returns_village_details_by_code(): void
    {
        // ARRANGE
        Province::create(['code' => '61', 'name' => 'Kalimantan Barat']);
        City::create(['code' => '6101', 'province_code' => '61', 'name' => 'Kab. Sambas']);
        District::create(['code' => '610101', 'city_code' => '6101', 'name' => 'Sambas']);
        Village::create(['code' => '61010101', 'district_code' => '610101', 'name' => 'Sebayan']);

        // ACT
        $response = $this->getJson('/api/indonesia/villages/61010101');

        // ASSERT
        $response->assertStatus(200);
        $response->assertJsonFragment([
            'code' => '61010101',
            'name' => 'Sebayan',
        ]);
    }

    /**
     * Test: Show village returns 404 when village not found
     */
    public function test_show_village_returns_404_when_village_not_found(): void
    {
        // ACT
        $response = $this->getJson('/api/indonesia/villages/99999999');

        // ASSERT
        $response->assertStatus(404);
        $response->assertJsonFragment(['message' => __('Village not found.')]);
    }
}
