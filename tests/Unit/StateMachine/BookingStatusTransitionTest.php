<?php

namespace Tests\Unit\StateMachine;

use App\Enums\BookingStatus;
use PHPUnit\Framework\TestCase;

/**
 * Pure Unit Tests for BookingStatus State Machine.
 *
 * Tests the enum's `canTransitionTo()` method and `validTransitions()`
 * in complete isolation — no database required.
 */
class BookingStatusTransitionTest extends TestCase
{
    // ─── Valid Forward Transitions ────────────────────────────────────────────

    /** @test */
    public function draft_can_transition_to_requested(): void
    {
        $this->assertTrue(
            BookingStatus::DRAFT->canTransitionTo(BookingStatus::REQUESTED)
        );
    }

    /** @test */
    public function requested_can_transition_to_accepted(): void
    {
        $this->assertTrue(
            BookingStatus::REQUESTED->canTransitionTo(BookingStatus::ACCEPTED)
        );
    }

    /** @test */
    public function requested_can_transition_to_rejected(): void
    {
        $this->assertTrue(
            BookingStatus::REQUESTED->canTransitionTo(BookingStatus::REJECTED)
        );
    }

    /** @test */
    public function accepted_can_transition_to_scheduled(): void
    {
        $this->assertTrue(
            BookingStatus::ACCEPTED->canTransitionTo(BookingStatus::SCHEDULED)
        );
    }

    /** @test */
    public function accepted_can_transition_to_cancelled(): void
    {
        $this->assertTrue(
            BookingStatus::ACCEPTED->canTransitionTo(BookingStatus::CANCELLED)
        );
    }

    /** @test */
    public function scheduled_can_transition_to_completed(): void
    {
        $this->assertTrue(
            BookingStatus::SCHEDULED->canTransitionTo(BookingStatus::COMPLETED)
        );
    }

    /** @test */
    public function scheduled_can_transition_to_cancelled(): void
    {
        $this->assertTrue(
            BookingStatus::SCHEDULED->canTransitionTo(BookingStatus::CANCELLED)
        );
    }

    /** @test */
    public function completed_can_transition_to_reviewed(): void
    {
        $this->assertTrue(
            BookingStatus::COMPLETED->canTransitionTo(BookingStatus::REVIEWED)
        );
    }

    // ─── Terminal States Have No Transitions ──────────────────────────────────

    /** @test */
    public function rejected_has_no_valid_transitions(): void
    {
        $this->assertEmpty(BookingStatus::REJECTED->validTransitions());
    }

    /** @test */
    public function cancelled_has_no_valid_transitions(): void
    {
        $this->assertEmpty(BookingStatus::CANCELLED->validTransitions());
    }

    /** @test */
    public function reviewed_has_no_valid_transitions(): void
    {
        $this->assertEmpty(BookingStatus::REVIEWED->validTransitions());
    }

    // ─── Invalid / Skip Transitions ───────────────────────────────────────────

    /** @test */
    public function draft_cannot_transition_to_completed(): void
    {
        $this->assertFalse(
            BookingStatus::DRAFT->canTransitionTo(BookingStatus::COMPLETED)
        );
    }

    /** @test */
    public function requested_cannot_transition_to_scheduled(): void
    {
        $this->assertFalse(
            BookingStatus::REQUESTED->canTransitionTo(BookingStatus::SCHEDULED)
        );
    }

    /** @test */
    public function requested_cannot_transition_to_completed(): void
    {
        $this->assertFalse(
            BookingStatus::REQUESTED->canTransitionTo(BookingStatus::COMPLETED)
        );
    }

    /** @test */
    public function accepted_cannot_transition_to_reviewed(): void
    {
        $this->assertFalse(
            BookingStatus::ACCEPTED->canTransitionTo(BookingStatus::REVIEWED)
        );
    }

    /** @test */
    public function scheduled_cannot_transition_to_reviewed_directly(): void
    {
        $this->assertFalse(
            BookingStatus::SCHEDULED->canTransitionTo(BookingStatus::REVIEWED)
        );
    }

    /** @test */
    public function completed_cannot_transition_to_cancelled(): void
    {
        $this->assertFalse(
            BookingStatus::COMPLETED->canTransitionTo(BookingStatus::CANCELLED)
        );
    }

    /** @test */
    public function reviewed_cannot_transition_to_completed(): void
    {
        $this->assertFalse(
            BookingStatus::REVIEWED->canTransitionTo(BookingStatus::COMPLETED)
        );
    }

    /** @test */
    public function rejected_cannot_transition_to_accepted(): void
    {
        $this->assertFalse(
            BookingStatus::REJECTED->canTransitionTo(BookingStatus::ACCEPTED)
        );
    }

    /** @test */
    public function cancelled_cannot_transition_to_scheduled(): void
    {
        $this->assertFalse(
            BookingStatus::CANCELLED->canTransitionTo(BookingStatus::SCHEDULED)
        );
    }

    // ─── Helper Methods ───────────────────────────────────────────────────────

    /** @test */
    public function isTerminal_returns_true_for_terminal_statuses(): void
    {
        $this->assertTrue(BookingStatus::REJECTED->isTerminal());
        $this->assertTrue(BookingStatus::CANCELLED->isTerminal());
        $this->assertTrue(BookingStatus::REVIEWED->isTerminal());
    }

    /** @test */
    public function isTerminal_returns_false_for_active_statuses(): void
    {
        $this->assertFalse(BookingStatus::DRAFT->isTerminal());
        $this->assertFalse(BookingStatus::REQUESTED->isTerminal());
        $this->assertFalse(BookingStatus::ACCEPTED->isTerminal());
        $this->assertFalse(BookingStatus::SCHEDULED->isTerminal());
        $this->assertFalse(BookingStatus::COMPLETED->isTerminal());
    }

    /** @test */
    public function canBeReviewed_returns_true_only_for_completed_and_reviewed(): void
    {
        $this->assertTrue(BookingStatus::COMPLETED->canBeReviewed());
        $this->assertTrue(BookingStatus::REVIEWED->canBeReviewed());

        $this->assertFalse(BookingStatus::DRAFT->canBeReviewed());
        $this->assertFalse(BookingStatus::REQUESTED->canBeReviewed());
        $this->assertFalse(BookingStatus::ACCEPTED->canBeReviewed());
        $this->assertFalse(BookingStatus::SCHEDULED->canBeReviewed());
        $this->assertFalse(BookingStatus::REJECTED->canBeReviewed());
        $this->assertFalse(BookingStatus::CANCELLED->canBeReviewed());
    }

    /** @test */
    public function canCancel_returns_true_only_for_accepted_and_scheduled(): void
    {
        $this->assertTrue(BookingStatus::ACCEPTED->canCancel());
        $this->assertTrue(BookingStatus::SCHEDULED->canCancel());

        $this->assertFalse(BookingStatus::DRAFT->canCancel());
        $this->assertFalse(BookingStatus::REQUESTED->canCancel());
        $this->assertFalse(BookingStatus::COMPLETED->canCancel());
        $this->assertFalse(BookingStatus::REVIEWED->canCancel());
        $this->assertFalse(BookingStatus::REJECTED->canCancel());
        $this->assertFalse(BookingStatus::CANCELLED->canCancel());
    }

    /** @test */
    public function canRespond_returns_true_only_for_requested(): void
    {
        $this->assertTrue(BookingStatus::REQUESTED->canRespond());

        foreach (BookingStatus::cases() as $status) {
            if ($status !== BookingStatus::REQUESTED) {
                $this->assertFalse($status->canRespond(), "Status {$status->value} should not be respondable");
            }
        }
    }

    /** @test */
    public function validTransitions_returns_expected_array_for_each_status(): void
    {
        $this->assertSame([BookingStatus::REQUESTED], BookingStatus::DRAFT->validTransitions());

        $this->assertEqualsCanonicalizing(
            [BookingStatus::ACCEPTED, BookingStatus::REJECTED],
            BookingStatus::REQUESTED->validTransitions()
        );

        $this->assertEqualsCanonicalizing(
            [BookingStatus::SCHEDULED, BookingStatus::CANCELLED],
            BookingStatus::ACCEPTED->validTransitions()
        );

        $this->assertEqualsCanonicalizing(
            [BookingStatus::COMPLETED, BookingStatus::CANCELLED],
            BookingStatus::SCHEDULED->validTransitions()
        );

        $this->assertSame([BookingStatus::REVIEWED], BookingStatus::COMPLETED->validTransitions());
    }
}
