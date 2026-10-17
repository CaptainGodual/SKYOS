<?php

namespace Tests\Unit;

use App\Models\ApiToken;
use App\Models\CrewMessage;
use App\Models\Flight;
use App\Models\Pirep;
use App\Models\TourBooking;
use App\Models\User;
use PHPUnit\Framework\TestCase;

/**
 * Pure logic that needs neither a database nor a booted application.
 */
class ModelHelpersTest extends TestCase
{
    public function test_tokens_are_hashed_deterministically(): void
    {
        $hash = ApiToken::hashToken('a-high-entropy-token');

        $this->assertSame(hash('sha256', 'a-high-entropy-token'), $hash);
        $this->assertSame($hash, ApiToken::hashToken('a-high-entropy-token'));
        $this->assertNotSame($hash, ApiToken::hashToken('a-high-entropy-token '));
    }

    public function test_it_knows_which_pirep_statuses_have_been_accepted(): void
    {
        $pirep = new Pirep;

        foreach (Pirep::ACCEPTED_STATUSES as $status) {
            $pirep->status = $status;

            $this->assertTrue($pirep->isAccepted(), "{$status} should count as accepted.");
        }

        $rejected = array_diff(Pirep::STATUSES, Pirep::ACCEPTED_STATUSES);

        $this->assertNotEmpty($rejected);

        foreach ($rejected as $status) {
            $pirep->status = $status;

            $this->assertFalse($pirep->isAccepted(), "{$status} should not count as accepted.");
        }
    }

    public function test_the_accepted_statuses_are_all_valid_statuses(): void
    {
        $this->assertEmpty(array_diff(Pirep::ACCEPTED_STATUSES, Pirep::STATUSES));
        $this->assertSame(Pirep::STATUSES, array_values(array_unique(Pirep::STATUSES)));
    }

    public function test_a_flight_status_list_covers_the_four_schedule_states(): void
    {
        $this->assertSame(['Open', 'Booked', 'Canceled', 'Completed'], Flight::STATUSES);
    }

    public function test_it_knows_which_tour_bookings_still_hold_a_tour(): void
    {
        $booking = new TourBooking;

        foreach (TourBooking::ACTIVE_STATUSES as $status) {
            $booking->status = $status;

            $this->assertTrue($booking->isActive(), "{$status} should count as active.");
        }

        foreach ([TourBooking::STATUS_COMPLETED, TourBooking::STATUS_CANCELLED] as $status) {
            $booking->status = $status;

            $this->assertFalse($booking->isActive(), "{$status} should not count as active.");
        }
    }

    public function test_it_shortens_a_name_to_a_first_name_and_surname_initial(): void
    {
        $this->assertSame('Piper A.', User::shortName('Piper Alpha'));
        $this->assertSame('Jean-Luc P.', User::shortName('Jean-Luc Picard'));
        $this->assertSame('Piper B.', User::shortName('Piper van der Berg'));
        $this->assertSame('Piper A.', User::shortName('Piper Alpha-Bravo'));
        $this->assertSame('Piper A.', User::shortName('  Piper   Alpha  '));
        $this->assertSame('Åsa Ö.', User::shortName('Åsa Öberg'));
    }

    public function test_a_name_that_is_already_short_is_left_alone(): void
    {
        $this->assertSame('Madonna', User::shortName('Madonna'));
        $this->assertSame('Piper A.', User::shortName('Piper A.'));
        $this->assertSame('Piper A.', User::shortName('Piper A'));
    }

    public function test_a_missing_name_still_reads_as_a_crew_member(): void
    {
        $this->assertSame('Crew member', User::shortName(null));
        $this->assertSame('Crew member', User::shortName('   '));
    }

    public function test_the_display_name_and_avatar_initial_come_from_the_stored_name(): void
    {
        $user = new User;
        $user->name = 'Piper Alpha';

        $this->assertSame('Piper A.', $user->displayName());
        $this->assertSame('P', $user->initial());

        $user->name = '   ';

        $this->assertSame('Crew member', $user->displayName());
        $this->assertSame('', $user->initial());
    }

    public function test_the_names_copied_onto_flights_and_messages_are_shortened_too(): void
    {
        $flight = new Flight;
        $flight->pilot_name = 'Piper Alpha';

        $this->assertSame('Piper A.', $flight->pilotDisplayName());

        $message = new CrewMessage;
        $message->sender_name = 'Piper Alpha';
        $message->recipient_name = 'Dana Bravo';

        $this->assertSame('Piper A.', $message->senderDisplayName());
        $this->assertSame('Dana B.', $message->recipientDisplayName());
    }
}
