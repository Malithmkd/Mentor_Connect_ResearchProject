<?php

namespace Tests\Unit\StateMachine;

use App\Enums\RelationshipStatus;
use PHPUnit\Framework\TestCase;

/**
 * Pure Unit Tests for RelationshipStatus State Machine.
 *
 * Tests the enum's `canTransitionTo()` in complete isolation — no database.
 */
class RelationshipStatusTransitionTest extends TestCase
{
    // ─── Valid Transitions ────────────────────────────────────────────────────

    /** @test */
    public function pending_can_transition_to_accepted(): void
    {
        $this->assertTrue(
            RelationshipStatus::PENDING->canTransitionTo(RelationshipStatus::ACCEPTED)
        );
    }

    /** @test */
    public function pending_can_transition_to_declined(): void
    {
        $this->assertTrue(
            RelationshipStatus::PENDING->canTransitionTo(RelationshipStatus::DECLINED)
        );
    }

    /** @test */
    public function accepted_can_transition_to_ended(): void
    {
        $this->assertTrue(
            RelationshipStatus::ACCEPTED->canTransitionTo(RelationshipStatus::ENDED)
        );
    }

    // ─── Invalid Transitions ──────────────────────────────────────────────────

    /** @test */
    public function pending_cannot_transition_to_ended(): void
    {
        $this->assertFalse(
            RelationshipStatus::PENDING->canTransitionTo(RelationshipStatus::ENDED)
        );
    }

    /** @test */
    public function pending_cannot_transition_to_itself(): void
    {
        $this->assertFalse(
            RelationshipStatus::PENDING->canTransitionTo(RelationshipStatus::PENDING)
        );
    }

    /** @test */
    public function accepted_cannot_transition_to_declined(): void
    {
        $this->assertFalse(
            RelationshipStatus::ACCEPTED->canTransitionTo(RelationshipStatus::DECLINED)
        );
    }

    /** @test */
    public function accepted_cannot_transition_to_pending(): void
    {
        $this->assertFalse(
            RelationshipStatus::ACCEPTED->canTransitionTo(RelationshipStatus::PENDING)
        );
    }

    /** @test */
    public function declined_cannot_transition_to_anything(): void
    {
        foreach (RelationshipStatus::cases() as $target) {
            $this->assertFalse(
                RelationshipStatus::DECLINED->canTransitionTo($target),
                "DECLINED should not be able to transition to {$target->value}"
            );
        }
    }

    /** @test */
    public function ended_cannot_transition_to_anything(): void
    {
        foreach (RelationshipStatus::cases() as $target) {
            $this->assertFalse(
                RelationshipStatus::ENDED->canTransitionTo($target),
                "ENDED should not be able to transition to {$target->value}"
            );
        }
    }

    /** @test */
    public function all_valid_transitions_are_fully_enumerated(): void
    {
        // PENDING → [ACCEPTED, DECLINED]
        $pendingValid = array_filter(
            RelationshipStatus::cases(),
            fn ($s) => RelationshipStatus::PENDING->canTransitionTo($s)
        );
        $this->assertCount(2, $pendingValid);

        // ACCEPTED → [ENDED]
        $acceptedValid = array_filter(
            RelationshipStatus::cases(),
            fn ($s) => RelationshipStatus::ACCEPTED->canTransitionTo($s)
        );
        $this->assertCount(1, $acceptedValid);

        // DECLINED → []
        $declinedValid = array_filter(
            RelationshipStatus::cases(),
            fn ($s) => RelationshipStatus::DECLINED->canTransitionTo($s)
        );
        $this->assertCount(0, $declinedValid);

        // ENDED → []
        $endedValid = array_filter(
            RelationshipStatus::cases(),
            fn ($s) => RelationshipStatus::ENDED->canTransitionTo($s)
        );
        $this->assertCount(0, $endedValid);
    }

    /** @test */
    public function labels_are_correct_for_all_statuses(): void
    {
        $this->assertSame('Pending', RelationshipStatus::PENDING->label());
        $this->assertSame('Active', RelationshipStatus::ACCEPTED->label());
        $this->assertSame('Declined', RelationshipStatus::DECLINED->label());
        $this->assertSame('Ended', RelationshipStatus::ENDED->label());
    }
}
