{{-- the message box shared by a new ticket and a reply: Markdown, a visible character limit and screenshots --}}
<x-field name="body" :label="$label" type="textarea" rows="8" required maxlength="{{ \App\Http\Controllers\SupportTicketController::MAX_BODY }}" counter
         :hint="__('Markdown works: **bold**, *italics*, `code`, lists and links. Never include a password or a sign-in code.')" />
<div class="field">
    <label for="f-images" class="field-label">{{ __('Screenshots') }} <span class="font-normal text-muted">({{ __('optional') }})</span></label>
    <input id="f-images" name="images[]" type="file" multiple accept="image/png,image/jpeg,image/webp,image/gif" class="input" aria-describedby="f-images-hint">
    <p id="f-images-hint" class="field-hint">{{ __('Up to :count images, 5 MB each. Only you and the support team can see them.', ['count' => \App\Http\Controllers\SupportTicketController::MAX_IMAGES]) }}</p>
    @error('images')<p class="field-error">{{ $message }}</p>@enderror
    @error('images.*')<p class="field-error">{{ $message }}</p>@enderror
</div>
