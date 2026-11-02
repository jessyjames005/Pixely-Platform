<?php

declare(strict_types=1);

return [
    /* Exact host names only. Keep this allow-list under operator control. */
    'artifact_hosts' => [
        'pixely' => ['updates.pixely.example'],
        'github' => ['github.com', 'objects.githubusercontent.com', 'release-assets.githubusercontent.com'],
        'gitlab' => ['gitlab.com'],
    ],

    /* Official Registry endpoint. Replace the example host in production configuration. */
    'registry_base_url' => env('PIXELY_REGISTRY_URL', 'https://registry.pixely.example'),
    'registry_timeout_seconds' => (int) env('PIXELY_REGISTRY_TIMEOUT', 10),
    'registry_per_page' => (int) env('PIXELY_REGISTRY_PER_PAGE', 20),

    /* Base64-encoded Ed25519 public keys, indexed by locally trusted key ID. */
    'signing_public_keys' => [],
    'max_artifact_bytes' => 104857600,
    'download_timeout_seconds' => 60,
];
