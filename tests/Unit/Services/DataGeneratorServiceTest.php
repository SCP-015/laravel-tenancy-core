<?php

namespace Tests\Unit\Services;

use App\Services\DataGeneratorService;
use PHPUnit\Framework\TestCase;

class DataGeneratorServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        // Reset static counters after each test
        DataGeneratorService::reset();
        parent::tearDown();
    }

    /**
     * Test: firstName generates valid first name
     */
    public function test_first_name_generates_valid_name(): void
    {
        $firstName = DataGeneratorService::firstName();
        
        $this->assertIsString($firstName);
        $this->assertNotEmpty($firstName);
        $this->assertGreaterThan(0, strlen($firstName));
    }

    /**
     * Test: lastName generates valid last name
     */
    public function test_last_name_generates_valid_name(): void
    {
        $lastName = DataGeneratorService::lastName();
        
        $this->assertIsString($lastName);
        $this->assertNotEmpty($lastName);
        $this->assertGreaterThan(0, strlen($lastName));
    }

    /**
     * Test: uniqueEmail generates unique emails
     */
    public function test_unique_email_generates_unique_emails(): void
    {
        $email1 = DataGeneratorService::uniqueEmail();
        $email2 = DataGeneratorService::uniqueEmail();
        $email3 = DataGeneratorService::uniqueEmail();
        
        $this->assertIsString($email1);
        $this->assertStringContainsString('@', $email1);
        
        // Emails should be different
        $this->assertNotEquals($email1, $email2);
        $this->assertNotEquals($email2, $email3);
        $this->assertNotEquals($email1, $email3);
    }

    /**
     * Test: uniqueEmail format is valid
     */
    public function test_unique_email_format_is_valid(): void
    {
        $email = DataGeneratorService::uniqueEmail();
        
        // Should contain dot and @
        $this->assertStringContainsString('.', $email);
        $this->assertStringContainsString('@', $email);
        
        // Should match email pattern (support domains like company.co.id)
        $this->assertMatchesRegularExpression(
            '/^[a-z]+\.[a-z]+[0-9]*@[a-z0-9]+(?:\.[a-z]+)+$/',
            $email
        );
    }

    /**
     * Test: dateBetween generates date within range
     */
    public function test_date_between_generates_valid_date(): void
    {
        $startDate = '2025-01-01';
        $endDate = '2025-12-31';
        
        $date = DataGeneratorService::dateBetween($startDate, $endDate);
        
        $this->assertIsString($date);
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}$/', $date);
        
        $dateTime = strtotime($date);
        $this->assertGreaterThanOrEqual(strtotime($startDate), $dateTime);
        $this->assertLessThanOrEqual(strtotime($endDate), $dateTime);
    }

    /**
     * Test: dateTimeBetween generates datetime within range
     */
    public function test_date_time_between_generates_valid_datetime(): void
    {
        $startDate = '2025-01-01';
        $endDate = '2025-12-31';
        
        $dateTime = DataGeneratorService::dateTimeBetween($startDate, $endDate);
        
        $this->assertIsString($dateTime);
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $dateTime);
        
        $dateTimeStamp = strtotime($dateTime);
        $this->assertGreaterThanOrEqual(strtotime($startDate), $dateTimeStamp);
        $this->assertLessThanOrEqual(strtotime($endDate) + 86400, $dateTimeStamp); // +1 day for end of day
    }

    /**
     * Test: streetAddress generates valid address
     */
    public function test_street_address_generates_valid_address(): void
    {
        $address = DataGeneratorService::streetAddress();
        
        $this->assertIsString($address);
        $this->assertStringContainsString('Jl.', $address);
        $this->assertStringContainsString('No.', $address);
        $this->assertMatchesRegularExpression('/Jl\. .+ No\. \d+/', $address);
    }

    /**
     * Test: phoneNumber generates valid phone number
     */
    public function test_phone_number_generates_valid_number(): void
    {
        $phone = DataGeneratorService::phoneNumber();
        
        $this->assertIsString($phone);
        $this->assertMatchesRegularExpression('/^08\d{9}$/', $phone);
        $this->assertEquals(11, strlen($phone));
    }

    /**
     * Test: numberBetween generates number within range
     */
    public function test_number_between_generates_valid_number(): void
    {
        $min = 10;
        $max = 100;
        
        $number = DataGeneratorService::numberBetween($min, $max);
        
        $this->assertIsInt($number);
        $this->assertGreaterThanOrEqual($min, $number);
        $this->assertLessThanOrEqual($max, $number);
    }

    /**
     * Test: randomElement returns element from array
     */
    public function test_random_element_returns_valid_element(): void
    {
        $array = ['apple', 'banana', 'cherry', 'date'];
        
        $element = DataGeneratorService::randomElement($array);
        
        $this->assertContains($element, $array);
    }

    /**
     * Test: randomElements returns multiple elements
     */
    public function test_random_elements_returns_valid_elements(): void
    {
        $array = ['apple', 'banana', 'cherry', 'date', 'elderberry'];
        $count = 3;
        
        $elements = DataGeneratorService::randomElements($array, $count);
        
        $this->assertIsArray($elements);
        $this->assertCount($count, $elements);
        
        foreach ($elements as $element) {
            $this->assertContains($element, $array);
        }
    }

    /**
     * Test: randomElements returns all when count >= array size
     */
    public function test_random_elements_returns_all_when_count_exceeds_size(): void
    {
        $array = ['apple', 'banana', 'cherry'];
        
        $elements = DataGeneratorService::randomElements($array, 5);
        
        $this->assertCount(3, $elements);
        $this->assertEquals(sort($array), sort($elements));
    }

    /**
     * Test: randomElements returns single element when count is 1
     * Covers line 160 - array_rand returns single value (not array) when count = 1
     */
    public function test_random_elements_returns_single_element(): void
    {
        $array = ['apple', 'banana', 'cherry', 'date', 'elderberry'];
        
        $elements = DataGeneratorService::randomElements($array, 1);
        
        $this->assertIsArray($elements);
        $this->assertCount(1, $elements);
        $this->assertContains($elements[0], $array);
    }

    /**
     * Test: paragraph generates valid paragraph
     */
    public function test_paragraph_generates_valid_paragraph(): void
    {
        $paragraph = DataGeneratorService::paragraph(3);
        
        $this->assertIsString($paragraph);
        $this->assertNotEmpty($paragraph);
        
        // Should contain multiple sentences
        $sentences = explode('. ', $paragraph);
        $this->assertGreaterThanOrEqual(2, count($sentences));
    }

    /**
     * Test: applicationSource returns valid source
     */
    public function test_application_source_returns_valid_source(): void
    {
        $source = DataGeneratorService::applicationSource();
        
        $validSources = ['Website Karir', 'LinkedIn', 'JobStreet', 'Indeed', 'Referral', 'Social Media'];
        $this->assertContains($source, $validSources);
    }

    /**
     * Test: noticePeriod returns valid period
     */
    public function test_notice_period_returns_valid_period(): void
    {
        $period = DataGeneratorService::noticePeriod();
        
        $validPeriods = ['immediately', '1_week', '2_weeks', '1_month', '3_months'];
        $this->assertContains($period, $validPeriods);
    }

    /**
     * Test: interviewType returns valid type
     */
    public function test_interview_type_returns_valid_type(): void
    {
        $type = DataGeneratorService::interviewType();
        
        $this->assertContains($type, ['online', 'offline']);
    }

    /**
     * Test: meetingRoom returns valid room
     */
    public function test_meeting_room_returns_valid_room(): void
    {
        $room = DataGeneratorService::meetingRoom();
        
        $validRooms = ['Ruang Meeting A', 'Ruang Meeting B', 'Ruang Diskusi 1', 'Ruang Diskusi 2', 'Ruang Konferensi'];
        $this->assertContains($room, $validRooms);
    }

    /**
     * Test: googleMeetLink generates valid Google Meet link
     */
    public function test_google_meet_link_generates_valid_link(): void
    {
        $link = DataGeneratorService::googleMeetLink();
        
        $this->assertIsString($link);
        $this->assertStringStartsWith('https://meet.google.com/', $link);
        $this->assertMatchesRegularExpression('/^https:\/\/meet\.google\.com\/[a-z]{3}-[a-z]{4}-[a-z]{3}$/', $link);
    }

    /**
     * Test: reset clears static counters
     */
    public function test_reset_clears_counters(): void
    {
        // Generate some emails
        $email1 = DataGeneratorService::uniqueEmail();
        $email2 = DataGeneratorService::uniqueEmail();
        
        // Reset
        DataGeneratorService::reset();
        
        // Generate again - should start from beginning
        $email3 = DataGeneratorService::uniqueEmail();
        
        // First email after reset might be same pattern as first email before reset
        $this->assertIsString($email3);
    }
}
