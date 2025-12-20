<?php

namespace Tests\Feature\Central;

use App\Models\Indonesia\City;
use App\Models\Indonesia\District;
use App\Models\Indonesia\Province;
use App\Models\Indonesia\Village;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Tests\TestCase;

/**
 * Test untuk Indonesia Location Models (Structure Tests Only)
 * 
 * NOTE: These models use landlord DB which is not properly configured in test environment.
 * We test model structure, fillables, and relationship definitions without database queries.
 * 
 * Coverage: Model structure & relationships definition
 */
class IndonesiaModelsTest extends TestCase
{

    /**
     * Test: Province has cities() relation defined
     */
    public function test_province_has_cities_relation(): void
    {
        $province = new Province();
        $relation = $province->cities();
        
        $this->assertInstanceOf(HasMany::class, $relation);
        $this->assertEquals('province_code', $relation->getForeignKeyName());
        $this->assertEquals('code', $relation->getLocalKeyName());
    }

    /**
     * Test: City has province() relation defined
     */
    public function test_city_has_province_relation(): void
    {
        $city = new City();
        $relation = $city->province();
        
        $this->assertInstanceOf(BelongsTo::class, $relation);
        $this->assertEquals('province_code', $relation->getForeignKeyName());
        $this->assertEquals('code', $relation->getOwnerKeyName());
    }
    
    /**
     * Test: City has districts() relation defined
     */
    public function test_city_has_districts_relation(): void
    {
        $city = new City();
        $relation = $city->districts();
        
        $this->assertInstanceOf(HasMany::class, $relation);
        $this->assertEquals('city_code', $relation->getForeignKeyName());
    }

    /**
     * Test: District has city() relation defined
     */
    public function test_district_has_city_relation(): void
    {
        $district = new District();
        $relation = $district->city();
        
        $this->assertInstanceOf(BelongsTo::class, $relation);
        $this->assertEquals('city_code', $relation->getForeignKeyName());
    }
    
    /**
     * Test: District has villages() relation defined
     */
    public function test_district_has_villages_relation(): void
    {
        $district = new District();
        $relation = $district->villages();
        
        $this->assertInstanceOf(HasMany::class, $relation);
        $this->assertEquals('district_code', $relation->getForeignKeyName());
    }

    /**
     * Test: Village has district() relation defined
     */
    public function test_village_has_district_relation(): void
    {
        $village = new Village();
        $relation = $village->district();
        
        $this->assertInstanceOf(BelongsTo::class, $relation);
        $this->assertEquals('district_code', $relation->getForeignKeyName());
    }

    /**
     * Test: Province fillable attributes
     */
    public function test_province_fillable(): void
    {
        $province = new Province();
        $fillable = $province->getFillable();
        
        $this->assertContains('code', $fillable);
        $this->assertContains('name', $fillable);
        $this->assertContains('meta', $fillable);
    }
    
    /**
     * Test: City fillable attributes
     */
    public function test_city_fillable(): void
    {
        $city = new City();
        $fillable = $city->getFillable();
        
        $this->assertContains('code', $fillable);
        $this->assertContains('province_code', $fillable);
        $this->assertContains('name', $fillable);
    }
    
    /**
     * Test: District fillable attributes
     */
    public function test_district_fillable(): void
    {
        $district = new District();
        $fillable = $district->getFillable();
        
        $this->assertContains('code', $fillable);
        $this->assertContains('city_code', $fillable);
        $this->assertContains('name', $fillable);
    }

    /**
     * Test: Village fillable attributes
     */
    public function test_village_fillable(): void
    {
        $village = new Village();
        $fillable = $village->getFillable();
        
        $this->assertContains('code', $fillable);
        $this->assertContains('district_code', $fillable);
        $this->assertContains('name', $fillable);
    }
    
    /**
     * Test: All models have correct table names
     */
    public function test_table_names(): void
    {
        $this->assertEquals('id_provinces', (new Province())->getTable());
        $this->assertEquals('id_cities', (new City())->getTable());
        $this->assertEquals('id_districts', (new District())->getTable());
        $this->assertEquals('id_villages', (new Village())->getTable());
    }
}
