<?php

namespace Tests\Feature;

use App\Models\Airport;
use App\Models\CrewMessage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CrewMessageTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_from_the_inbox(): void
    {
        $this->get(route('messages.index'))->assertRedirect(route('login'));
    }

    public function test_a_message_only_reaches_the_pilot_it_is_addressed_to(): void
    {
        $sender = $this->pilot('Dispatcher');
        $recipient = $this->pilot('Piper Alpha');
        $bystander = $this->pilot('Bravo Tango');

        $this->actingAs($sender)->post(route('messages.store'), [
            'recipient_id' => $recipient->id,
            'subject' => 'Roster swap',
            'body' => 'Can you take my Tuesday rotation?',
        ])->assertSessionHas('success');

        $this->actingAs($recipient)->get(route('messages.index'))
            ->assertOk()
            ->assertSee('Roster swap')
            ->assertSee('Can you take my Tuesday rotation?');

        $this->actingAs($bystander)->get(route('messages.index'))
            ->assertOk()
            ->assertDontSee('Roster swap');
    }

    public function test_the_recipient_is_picked_by_account_not_by_name(): void
    {
        $sender = $this->pilot('Dispatcher');
        $first = $this->pilot('Alex Kim', Airport::factory()->create(['identifier' => 'EGLL']));
        $second = $this->pilot('Alex Kim', Airport::factory()->create(['identifier' => 'KORD']));

        // Two pilots can share a short name, so the picker points out the hub,
        // and it is the account behind the option that receives the mail.
        $this->actingAs($sender)->get(route('messages.index'))
            ->assertOk()
            ->assertSee('Alex K. · EGLL')
            ->assertSee('Alex K. · KORD');

        $this->actingAs($sender)->post(route('messages.store'), [
            'recipient_id' => $second->id,
            'subject' => 'Simulator check',
            'body' => 'Booked for Friday at 19:00Z.',
        ])->assertSessionHas('success');

        $firstSees = str_contains(
            $this->actingAs($first)->get(route('messages.index'))->getContent(),
            'Simulator check',
        );
        $secondSees = str_contains(
            $this->actingAs($second)->get(route('messages.index'))->getContent(),
            'Simulator check',
        );

        $this->assertFalse($firstSees, 'The namesake who was not picked should see nothing.');
        $this->assertTrue($secondSees, 'The account that was picked should see the message.');
    }

    public function test_the_inbox_shows_the_sender_without_their_surname(): void
    {
        $sender = $this->pilot('Dispatcher Person');
        $recipient = $this->pilot('Piper Alpha');

        $this->actingAs($sender)->post(route('messages.store'), [
            'recipient_id' => $recipient->id,
            'subject' => 'Welcome aboard',
            'body' => 'Your crew centre access is live.',
        ])->assertSessionHas('success');

        $this->actingAs($recipient)->get(route('messages.index'))
            ->assertOk()
            ->assertSee('Dispatcher P.')
            ->assertDontSee('Dispatcher Person');
    }

    public function test_a_message_can_only_be_sent_to_crew_in_good_standing(): void
    {
        $sender = $this->pilot('Dispatcher Person');
        $applicant = User::factory()->create([
            'name' => 'New Applicant',
            'approval_status' => User::APPROVAL_PENDING,
        ]);

        $this->actingAs($sender)->post(route('messages.store'), [
            'recipient_id' => $applicant->id,
            'subject' => 'Hello',
            'body' => 'Anybody there?',
        ])->assertSessionHasErrors('recipient_id');

        $this->actingAs($sender)->post(route('messages.store'), [
            'recipient_id' => $sender->id,
            'subject' => 'Note to self',
            'body' => 'Do not do this.',
        ])->assertSessionHasErrors('recipient_id');

        $this->assertDatabaseCount('crew_messages', 0);
    }

    public function test_renaming_a_pilot_does_not_lose_their_messages(): void
    {
        $sender = $this->pilot('Dispatcher');
        $pilot = $this->pilot('Piper Alpha');

        $this->actingAs($sender)->post(route('messages.store'), [
            'recipient_id' => $pilot->id,
            'subject' => 'Welcome aboard',
            'body' => 'Your crew centre access is live.',
        ])->assertSessionHas('success');

        $pilot->update(['name' => 'Piper Alpha-Bravo']);

        $this->actingAs($pilot)->get(route('messages.index'))
            ->assertOk()
            ->assertSee('Welcome aboard');
    }

    public function test_opening_the_inbox_clears_the_unread_badge(): void
    {
        $sender = $this->pilot('Dispatcher');
        $pilot = $this->pilot('Piper Alpha');

        CrewMessage::factory()->count(2)->sender($sender)->recipient($pilot)->create();

        $this->actingAs($pilot)->get('/')
            ->assertOk()
            ->assertSee('badge bg-info ms-auto">2</span>', false);

        $this->actingAs($pilot)->get(route('messages.index'))->assertOk();

        $this->assertSame(0, CrewMessage::query()->where('recipient_id', $pilot->id)->whereNull('read_at')->count());
        $this->assertSame(2, CrewMessage::query()->where('recipient_id', $pilot->id)->whereNotNull('read_at')->count());

        $this->actingAs($pilot)->get('/')
            ->assertOk()
            ->assertDontSee('badge bg-info ms-auto', false);
    }

    /**
     * Create an approved crew member with a fixed name and hub.
     */
    private function pilot(string $name, ?Airport $hub = null): User
    {
        return User::factory()->create([
            'name' => $name,
            'hub_id' => $hub?->id,
            'approval_status' => User::APPROVAL_APPROVED,
        ]);
    }
}
