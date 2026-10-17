<?php

namespace App\Http\Controllers;

use App\Models\CrewMessage;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MessageController extends Controller
{
    /**
     * Show the pilot's inbox.
     */
    public function index(Request $request): View
    {
        $user = $request->user();

        $messages = CrewMessage::query()
            ->where('recipient_id', $user->id)
            ->latest()
            ->paginate(20);

        // Opening the inbox is what clears the unread badge in the navigation,
        // so the count always means "mail you have not looked at yet".
        CrewMessage::query()
            ->where('recipient_id', $user->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return view('crew-center.messages.index', ['messages' => $messages]);
    }

    /**
     * Send a message to another crew member.
     */
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'recipient_name' => ['required', 'string', 'max:100', 'exists:users,name'],
            'subject' => ['required', 'string', 'max:120'],
            'body' => ['required', 'string', 'max:2000'],
        ], [
            'recipient_name.exists' => 'No crew member with that name was found.',
        ]);

        $sender = $request->user();
        $recipient = User::query()->where('name', $data['recipient_name'])->firstOrFail();

        CrewMessage::create([
            'sender_id' => $sender->id,
            'sender_name' => $sender->name,
            'recipient_id' => $recipient->id,
            'recipient_name' => $recipient->name,
            'subject' => $data['subject'],
            'body' => $data['body'],
        ]);

        return back()->with('success', 'Your message is on its way.');
    }
}
