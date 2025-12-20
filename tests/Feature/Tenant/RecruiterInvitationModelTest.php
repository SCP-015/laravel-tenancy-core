<?php

namespace Tests\Feature\Tenant;

use App\Models\Tenant\RecruiterInvitation;
use Carbon\Carbon;
use Tests\Feature\TenantTestCase;

/**
 * Test untuk RecruiterInvitation Model
 * 
 * Coverage target: 100%
 * - Lines 26-29: isExpired() method
 * - Lines 31-34: isValid() method
 * - Lines 21-24: Casts (expires_at, accepted_at)
 */
class RecruiterInvitationModelTest extends TenantTestCase
{
    /**
     * Test: isExpired() returns true when invitation has expired
     * 
     * Cover lines 26-29
     */
    public function test_is_expired_returns_true_when_invitation_expired(): void
    {
        // ARRANGE
        $invitation = RecruiterInvitation::create([
            'email' => 'newrecruiter@example.com',
            'invited_by_email' => 'admin@example.com',
            'code' => 'ABC123',
            'status' => 'pending',
            'expires_at' => Carbon::now()->subDay(), // Expired yesterday
        ]);

        // ACT
        $isExpired = $invitation->isExpired();

        // ASSERT
        $this->assertTrue($isExpired);
    }

    /**
     * Test: isExpired() returns false when invitation is still valid
     */
    public function test_is_expired_returns_false_when_invitation_not_expired(): void
    {
        // ARRANGE
        $invitation = RecruiterInvitation::create([
            'email' => 'newrecruiter@example.com',
            'invited_by_email' => 'admin@example.com',
            'code' => 'XYZ789',
            'status' => 'pending',
            'expires_at' => Carbon::now()->addDays(7), // Expires in 7 days
        ]);

        // ACT
        $isExpired = $invitation->isExpired();

        // ASSERT
        $this->assertFalse($isExpired);
    }

    /**
     * Test: isExpired() returns false when expires_at is null
     */
    public function test_is_expired_returns_false_when_expires_at_is_null(): void
    {
        // ARRANGE
        $invitation = RecruiterInvitation::create([
            'email' => 'newrecruiter@example.com',
            'invited_by_email' => 'admin@example.com',
            'code' => 'DEF456',
            'status' => 'pending',
            'expires_at' => null, // No expiration
        ]);

        // ACT
        $isExpired = $invitation->isExpired();

        // ASSERT
        $this->assertFalse($isExpired);
    }

    /**
     * Test: isValid() returns true when status is pending and not expired
     * 
     * Cover lines 31-34
     */
    public function test_is_valid_returns_true_when_pending_and_not_expired(): void
    {
        // ARRANGE
        $invitation = RecruiterInvitation::create([
            'email' => 'valid@example.com',
            'invited_by_email' => 'admin@example.com',
            'code' => 'VALID123',
            'status' => 'pending',
            'expires_at' => Carbon::now()->addWeek(),
        ]);

        // ACT
        $isValid = $invitation->isValid();

        // ASSERT
        $this->assertTrue($isValid);
    }

    /**
     * Test: isValid() returns false when status is not pending
     */
    public function test_is_valid_returns_false_when_status_not_pending(): void
    {
        // ARRANGE
        $invitation = RecruiterInvitation::create([
            'email' => 'accepted@example.com',
            'invited_by_email' => 'admin@example.com',
            'code' => 'ACC123',
            'status' => 'accepted',
            'expires_at' => Carbon::now()->addWeek(),
        ]);

        // ACT
        $isValid = $invitation->isValid();

        // ASSERT
        $this->assertFalse($isValid);
    }

    /**
     * Test: isValid() returns false when invitation is expired
     */
    public function test_is_valid_returns_false_when_expired(): void
    {
        // ARRANGE
        $invitation = RecruiterInvitation::create([
            'email' => 'expired@example.com',
            'invited_by_email' => 'admin@example.com',
            'code' => 'EXP123',
            'status' => 'pending',
            'expires_at' => Carbon::now()->subHours(1), // Expired 1 hour ago
        ]);

        // ACT
        $isValid = $invitation->isValid();

        // ASSERT
        $this->assertFalse($isValid);
    }

    /**
     * Test: Datetime casts work correctly
     * 
     * Cover lines 21-24: Casts
     */
    public function test_datetime_casts_work_correctly(): void
    {
        // ARRANGE
        $expiresAt = Carbon::now()->addDays(5);
        $acceptedAt = Carbon::now();
        
        $invitation = RecruiterInvitation::create([
            'email' => 'test@example.com',
            'invited_by_email' => 'admin@example.com',
            'code' => 'CAST123',
            'status' => 'accepted',
            'expires_at' => $expiresAt,
            'accepted_at' => $acceptedAt,
        ]);

        // ACT
        $invitation->refresh();

        // ASSERT
        $this->assertInstanceOf(Carbon::class, $invitation->expires_at);
        $this->assertInstanceOf(Carbon::class, $invitation->accepted_at);
        $this->assertEquals($expiresAt->format('Y-m-d H:i:s'), $invitation->expires_at->format('Y-m-d H:i:s'));
        $this->assertEquals($acceptedAt->format('Y-m-d H:i:s'), $invitation->accepted_at->format('Y-m-d H:i:s'));
    }

    /**
     * Test: Fillable attributes work correctly
     */
    public function test_fillable_attributes_work(): void
    {
        // ARRANGE & ACT
        $invitation = RecruiterInvitation::create([
            'email' => 'fillable@example.com',
            'invited_by_email' => 'inviter@example.com',
            'code' => 'FILL123',
            'status' => 'pending',
            'expires_at' => Carbon::now()->addWeek(),
            'accepted_at' => null,
        ]);

        // ASSERT
        $this->assertDatabaseHas('recruiter_invitations', [
            'id' => $invitation->id,
            'email' => 'fillable@example.com',
            'invited_by_email' => 'inviter@example.com',
            'code' => 'FILL123',
            'status' => 'pending',
        ]);
    }

    /**
     * Test: Multiple invitations can exist
     */
    public function test_multiple_invitations_can_exist(): void
    {
        // ARRANGE
        $invitation1 = RecruiterInvitation::create([
            'email' => 'recruiter1@example.com',
            'invited_by_email' => 'admin@example.com',
            'code' => 'CODE1',
            'status' => 'pending',
            'expires_at' => Carbon::now()->addWeek(),
        ]);
        
        $invitation2 = RecruiterInvitation::create([
            'email' => 'recruiter2@example.com',
            'invited_by_email' => 'admin@example.com',
            'code' => 'CODE2',
            'status' => 'accepted',
            'expires_at' => Carbon::now()->addWeek(),
            'accepted_at' => Carbon::now(),
        ]);

        // ACT & ASSERT
        $this->assertNotEquals($invitation1->id, $invitation2->id);
        $this->assertTrue($invitation1->isValid());
        $this->assertFalse($invitation2->isValid()); // Status is 'accepted', not 'pending'
    }
}
