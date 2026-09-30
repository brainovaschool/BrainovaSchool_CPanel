{{-- Usage: @include('frontend.partials.waitlist-form', ['category' => $category, 'launchNote' => 'Starts around March 2027']) --}}
<div class="fe-waitlist-form" style="background:#f4fbfa;border:1px solid #d8ece9;border-radius:16px;padding:28px 24px;max-width:520px;">
    <h3 style="margin-bottom:6px;">Join the waitlist</h3>
    <p style="color:#5b7370;margin-bottom:18px;">
        {{ $launchNote ?? 'Coming soon.' }} Leave your details and we'll message you on WhatsApp the moment it opens —
        no obligation, no payment now.
    </p>

    @if (session('waitlist_success'))
        <div class="alert alert-success" style="margin-bottom:16px;">{{ session('waitlist_success') }}</div>
    @endif
    @if (session('waitlist_error'))
        <div class="alert alert-danger" style="margin-bottom:16px;">{{ session('waitlist_error') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger" style="margin-bottom:16px;">
            <ul class="mb-0 ps-3">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('frontend.waitlist.store') }}" method="post">
        @csrf
        @if (isset($category))
            <input type="hidden" name="program_category_id" value="{{ $category->id }}">
        @endif
        <div class="mb-3">
            <label class="form-label">Parent's name</label>
            <input type="text" name="parent_name" class="form-control" value="{{ old('parent_name') }}" required>
        </div>
        <div class="mb-3">
            <label class="form-label">WhatsApp number</label>
            <input type="text" name="whatsapp_number" class="form-control" value="{{ old('whatsapp_number') }}" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Child's grade</label>
            <input type="text" name="child_grade" class="form-control" value="{{ old('child_grade') }}" placeholder="e.g. Grade 5">
        </div>
        {{-- H10: honeypot — invisible to people, bots fill it in and get silently rejected server-side --}}
        <input type="text" name="website" tabindex="-1" autocomplete="off" style="position:absolute;left:-9999px;width:1px;height:1px;opacity:0;">
        <div class="mb-3 form-check">
            <input class="form-check-input @error('consent') is-invalid @enderror" type="checkbox" name="consent" id="waitlistConsent" value="1" {{ old('consent') ? 'checked' : '' }} required>
            <label class="form-check-label" for="waitlistConsent" style="font-size:.85rem;">
                I agree to be contacted on WhatsApp about this program.
            </label>
            @error('consent')<small class="text-danger d-block">{{ $message }}</small>@enderror
        </div>
        <button type="submit" class="fe-btn-pill fe-btn-primary" style="border:none;">Join the waitlist</button>
    </form>
</div>
