@props(['record'])

@if (! $record->receipt_path)
    <span class="inline-flex" title="Will not backup due to no receipt attached">
        <svg class="w-3 h-3 text-red-500" fill="currentColor" viewBox="0 0 8 8"><circle cx="4" cy="4" r="4" /></svg>
    </span>
@elseif ($record->drive_backed_up_at)
    <span class="inline-flex" title="Backup completed in Google Drive">
        <svg class="w-3 h-3 text-green-500" fill="currentColor" viewBox="0 0 8 8"><circle cx="4" cy="4" r="4" /></svg>
    </span>
@else
    <span class="inline-flex" title="Pending backup on 7:00PM today">
        <svg class="w-3 h-3 text-gray-400" fill="currentColor" viewBox="0 0 8 8"><circle cx="4" cy="4" r="4" /></svg>
    </span>
@endif
