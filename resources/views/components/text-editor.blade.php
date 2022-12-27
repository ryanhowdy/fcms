    <textarea class="form-control border rounded-0" id="{{ $name ?? 'comments' }}" name="{{ $name ?? 'comments' }}" rows="5"></textarea>
    <div class="menu border border-top-0 p-1 d-flex justify-content-between">
        <div class="mt-1">
            <i class="p-1 bi-type-bold"></i>
            <i class="p-1 bi-type-underline"></i>
            <i class="p-1 bi-type-italic"></i>
            <i class="p-1 bi-text-left"></i>
            <i class="p-1 bi-text-center"></i>
            <i class="p-1 bi-text-right"></i>
            <i class="p-1 bi-chat"></i>
            <i class="p-1 bi-link"></i>
            <i class="p-1 bi-question"></i>
        </div>
        <div>
            <span class="mt-1 me-2">
@if (!isset($remove) || !in_array('images', $remove))
                <i class="p-1 me-1 bi-images"></i>
@endif
@if (!isset($remove) || !in_array('emojis', $remove))
                <i class="p-1 me-1 bi-emoji-smile"></i>
@endif
            </span>
@if (!isset($remove) || !in_array('submit', $remove))
            <input type="submit" class="btn btn-sm btn-secondary me-3" name="submit"/>
@endif
        </div>
    </div>
