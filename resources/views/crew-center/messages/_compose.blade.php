{{--
    The "send a message" form.

    The recipient is chosen from the crew directory rather than typed in:
    surnames are never published in the crew centre, so there is no full name
    to search for, and two pilots can honestly share the same short name.
--}}
@if ($crew->isEmpty())
    <p class="small text-body-secondary mb-0">There is nobody else on the crew list to write to yet.</p>
@else
    <form method="POST" action="{{ route('messages.store') }}" class="vstack gap-2">
        @csrf
        <div>
            <select class="form-select form-select-sm @error('recipient_id') is-invalid @enderror" name="recipient_id" aria-label="Recipient" required>
                <option value="">Send to…</option>
                @foreach ($crew as $member)
                    <option value="{{ $member->id }}" @selected((int) old('recipient_id') === $member->id)>{{ $member->displayName() }}@if ($member->homeHub) · {{ $member->homeHub->identifier }}@endif</option>
                @endforeach
            </select>
            @error('recipient_id')
                <div class="invalid-feedback d-block">{{ $message }}</div>
            @enderror
        </div>
        <div>
            <input class="form-control form-control-sm @error('subject') is-invalid @enderror" name="subject" value="{{ old('subject') }}" placeholder="Subject" required>
            @error('subject')
                <div class="invalid-feedback d-block">{{ $message }}</div>
            @enderror
        </div>
        <div>
            <textarea class="form-control form-control-sm @error('body') is-invalid @enderror" name="body" rows="3" placeholder="Message" required>{{ old('body') }}</textarea>
            @error('body')
                <div class="invalid-feedback d-block">{{ $message }}</div>
            @enderror
        </div>
        <button class="btn btn-sm btn-primary align-self-start" type="submit">Send message</button>
    </form>
@endif
