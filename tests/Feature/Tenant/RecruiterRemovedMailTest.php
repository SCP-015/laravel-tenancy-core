<?php

namespace Tests\Feature\Tenant;

use App\Mail\RecruiterRemoved;
use Tests\Feature\TenantTestCase;

/**
 * Test untuk RecruiterRemoved Mailable
 * 
 * Coverage target: 100%
 * - Constructor: Assign properties
 * - build(): Set subject dan view
 */
class RecruiterRemovedMailTest extends TenantTestCase
{
    /**
     * Test: build() method returns mailable with correct subject and view
     */
    public function test_build_returns_mailable_with_correct_subject_and_view(): void
    {
        // ARRANGE
        $senderMail = 'admin@company.com';
        $receiverMail = 'removed-recruiter@company.com';
        $tenantName = 'PT Teknologi Indonesia';

        // ACT
        $mailable = new RecruiterRemoved($senderMail, $receiverMail, $tenantName);
        $mailable->build();

        // ASSERT
        // Check subject
        $this->assertEquals(
            'Akun Admin NusaHire Anda Telah Dinonaktifkan',
            $mailable->subject
        );
        
        // Check view
        $this->assertEquals('emails.recruiter_removed', $mailable->view);
    }

    /**
     * Test: Mailable contains correct data for view
     */
    public function test_mailable_contains_correct_data_for_view(): void
    {
        // ARRANGE
        $senderMail = 'hr@company.com';
        $receiverMail = 'exrecruiter@company.com';
        $tenantName = 'Company Name';

        // ACT
        $mailable = new RecruiterRemoved($senderMail, $receiverMail, $tenantName);
        $mailable->build();

        // ASSERT - Check public properties
        $this->assertEquals($senderMail, $mailable->senderMail);
        $this->assertEquals($receiverMail, $mailable->receiverMail);
        $this->assertEquals($tenantName, $mailable->tenantName);
    }

    /**
     * Test: Subject is consistent regardless of tenant name
     */
    public function test_subject_is_consistent_for_all_tenants(): void
    {
        // ARRANGE & ACT
        $mailable1 = new RecruiterRemoved(
            'admin@company1.com',
            'user1@company1.com',
            'Company One'
        );
        $mailable1->build();
        
        $mailable2 = new RecruiterRemoved(
            'admin@company2.com',
            'user2@company2.com',
            'Company Two'
        );
        $mailable2->build();

        // ASSERT - Subject should be same for all
        $this->assertEquals($mailable1->subject, $mailable2->subject);
        $this->assertEquals(
            'Akun Admin NusaHire Anda Telah Dinonaktifkan',
            $mailable1->subject
        );
    }

    /**
     * Test: Mailable can be rendered with correct data
     */
    public function test_mailable_renders_with_correct_data(): void
    {
        // ARRANGE
        $senderMail = 'owner@company.com';
        $receiverMail = 'removed@company.com';
        $tenantName = 'Test Company';
        
        $mailable = new RecruiterRemoved($senderMail, $receiverMail, $tenantName);
        $mailable->build();
        
        // ACT - Render the mailable to verify no errors
        try {
            $rendered = $mailable->render();
        } catch (\Exception $e) {
            $this->fail('Mailable failed to render: ' . $e->getMessage());
        }
        
        // ASSERT - Verify output contains expected content
        $this->assertIsString($rendered);
        $this->assertNotEmpty($rendered);
        
        // Verify tenant name appears in rendered content
        $this->assertStringContainsString($tenantName, $rendered);
        
        // Verify it's HTML
        $this->assertMatchesRegularExpression('/<[^>]+>/', $rendered);
    }

    /**
     * Test: Constructor properly assigns all properties
     */
    public function test_constructor_assigns_all_properties(): void
    {
        // ARRANGE
        $senderMail = 'sender@test.com';
        $receiverMail = 'receiver@test.com';
        $tenantName = 'Test Tenant';

        // ACT
        $mailable = new RecruiterRemoved($senderMail, $receiverMail, $tenantName);

        // ASSERT - All properties should be set correctly
        $this->assertEquals($senderMail, $mailable->senderMail);
        $this->assertEquals($receiverMail, $mailable->receiverMail);
        $this->assertEquals($tenantName, $mailable->tenantName);
    }

    /**
     * Test: Mailable handles different email formats
     */
    public function test_mailable_handles_different_email_formats(): void
    {
        // ARRANGE & ACT
        $mailable = new RecruiterRemoved(
            'admin+test@company.co.id',
            'user.name+tag@example.com',
            'PT. Company Name'
        );
        $mailable->build();

        // ASSERT
        $this->assertEquals('admin+test@company.co.id', $mailable->senderMail);
        $this->assertEquals('user.name+tag@example.com', $mailable->receiverMail);
        $this->assertEquals('PT. Company Name', $mailable->tenantName);
    }
}
