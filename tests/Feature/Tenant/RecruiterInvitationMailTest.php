<?php

namespace Tests\Feature\Tenant;

use App\Mail\RecruiterInvitation;
use Tests\Feature\TenantTestCase;

/**
 * Test untuk RecruiterInvitation Mailable
 * 
 * Coverage target: 100%
 * - Constructor: Assign properties
 * - build(): Set subject dan view
 */
class RecruiterInvitationMailTest extends TenantTestCase
{
    /**
     * Test: build() method returns mailable with correct subject and view
     */
    public function test_build_returns_mailable_with_correct_subject_and_view(): void
    {
        // ARRANGE
        $senderMail = 'admin@company.com';
        $receiverMail = 'recruiter@company.com';
        $tenantName = 'PT Teknologi Indonesia';
        $tenantCode = 'TECH123';
        $inviteUrl = 'https://nusahire.com/invite/abc123';

        // ACT
        $mailable = new RecruiterInvitation(
            $senderMail,
            $receiverMail,
            $tenantName,
            $tenantCode,
            $inviteUrl
        );
        $mailable->build();

        // ASSERT
        // Check subject
        $this->assertEquals(
            'Undangan Admin ' . $tenantName . ' di NusaHire',
            $mailable->subject
        );
        
        // Check view
        $this->assertEquals('emails.recruiter_invitation', $mailable->view);
    }

    /**
     * Test: Mailable contains correct data for view
     */
    public function test_mailable_contains_correct_data_for_view(): void
    {
        // ARRANGE
        $senderMail = 'owner@startup.com';
        $receiverMail = 'newrecruiter@startup.com';
        $tenantName = 'Startup Innovation';
        $tenantCode = 'START456';
        $inviteUrl = 'https://nusahire.com/invite/xyz789';

        // ACT
        $mailable = new RecruiterInvitation(
            $senderMail,
            $receiverMail,
            $tenantName,
            $tenantCode,
            $inviteUrl
        );
        $mailable->build();

        // ASSERT - Check public properties
        $this->assertEquals($senderMail, $mailable->senderMail);
        $this->assertEquals($receiverMail, $mailable->receiverMail);
        $this->assertEquals($tenantName, $mailable->tenantName);
        $this->assertEquals($tenantCode, $mailable->tenantCode);
        $this->assertEquals($inviteUrl, $mailable->inviteUrl);
    }

    /**
     * Test: Subject changes based on tenant name
     */
    public function test_subject_changes_based_on_tenant_name(): void
    {
        // ARRANGE & ACT
        $mailable1 = new RecruiterInvitation(
            'admin@company1.com',
            'recruiter@company1.com',
            'Company One',
            'CODE1',
            'https://url1.com'
        );
        $mailable1->build();
        
        $mailable2 = new RecruiterInvitation(
            'admin@company2.com',
            'recruiter@company2.com',
            'Company Two',
            'CODE2',
            'https://url2.com'
        );
        $mailable2->build();

        // ASSERT
        $this->assertStringContainsString('Company One', $mailable1->subject);
        $this->assertStringContainsString('Company Two', $mailable2->subject);
        $this->assertNotEquals($mailable1->subject, $mailable2->subject);
    }

    /**
     * Test: Mailable can be rendered with correct data
     */
    public function test_mailable_renders_with_correct_data(): void
    {
        // ARRANGE
        $senderMail = 'hr@company.com';
        $receiverMail = 'newhr@company.com';
        $tenantName = 'Test Company';
        $tenantCode = 'TEST999';
        $inviteUrl = 'https://nusahire.com/invite/test123';
        
        $mailable = new RecruiterInvitation(
            $senderMail,
            $receiverMail,
            $tenantName,
            $tenantCode,
            $inviteUrl
        );
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
        $tenantCode = 'TEST123';
        $inviteUrl = 'https://test.com/invite';

        // ACT
        $mailable = new RecruiterInvitation(
            $senderMail,
            $receiverMail,
            $tenantName,
            $tenantCode,
            $inviteUrl
        );

        // ASSERT - All properties should be set correctly
        $this->assertEquals($senderMail, $mailable->senderMail);
        $this->assertEquals($receiverMail, $mailable->receiverMail);
        $this->assertEquals($tenantName, $mailable->tenantName);
        $this->assertEquals($tenantCode, $mailable->tenantCode);
        $this->assertEquals($inviteUrl, $mailable->inviteUrl);
    }
}
