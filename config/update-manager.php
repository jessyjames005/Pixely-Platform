<?php

declare(strict_types=1);

return [
    /* Exact host names only. Keep this allow-list under operator control. */
    'artifact_hosts' => [
        'pixely' => ['updates.pixely.example'],
        'github' => ['github.com', 'objects.githubusercontent.com', 'release-assets.githubusercontent.com'],
        'gitlab' => ['gitlab.com'],
    ],

    /* Base64-encoded Ed25519 public keys, indexed by key ID from trusted registry metadata. */
    'signing_public_keys' => [],
    'max_artifact_bytes' => 104857600,
    'download_timeout_seconds' => 60,
];
