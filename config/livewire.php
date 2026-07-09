<?php

return [
    'temporary_file_upload' => [
        'disk' => env('LIVEWIRE_UPLOAD_DISK', 's3'),  // 👈 important
        'rules' => ['file', 'max:'.env('TRACK_UPLOAD_MAX_KB', 204800)],
        'directory' => env('LIVEWIRE_TEMP_DIR', 'livewire-tmp'),
        'middleware' => 'throttle:60,1',
        'preview_mimes' => [
            'image/jpeg',
            'image/gif',
            'image/png',
            'image/svg+xml',
            'application/pdf',
            'text/plain',
        ],
    ],
];
