<?php

namespace Tests\Feature;

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
            'recipient_name' => 'Piper Alpha',
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

    public function test_two_pilots_with_the_same_name_do_not_share_a_mailbox(): void
    {
        $sender = $this->pilot('Dispatcher');
        $first = $this->pilot('Alex Kim');
        $second = $this->pilot('Alex Kim');

        $this->actingAs($sender)->post(route('messages.store'), [
            'recipient_name' => 'Alex Kim',
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

        // The name is ambiguous, but the message belongs to exactly one account.
        $this->assertTrue(($firstSees xor $secondSees), 'Exactly one namesake should receive the message.');
    }

    public function test_renaming_a_pilot_does_not_lose_their_messages(): void
    {
        $sender = $this->pilot('Dispatcher');
        $pilot = $this->pilot('Piper Alpha');

        $this->actingAs($sender)->post(route('messages.store'), [
            'recipient_name' => 'Piper Alpha',
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
     * Create an approved crew member with a fixed name.
     */
    private function pilot(string $name): User
    {
        return User::factory()->create([
            'name' => $name,
            'approval_status' => User::APPROVAL_APPROVED,
        ]);
    }
}
