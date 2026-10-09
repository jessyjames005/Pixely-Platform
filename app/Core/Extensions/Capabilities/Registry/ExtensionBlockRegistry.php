<?php

declare(strict_types=1);

namespace App\Core\Extensions\Capabilities\Registry;

use App\Core\Extensions\Capabilities\Contracts\ExtensionBlocksInterface;
use App\Core\Extensions\Contracts\ExtensionInterface;
use InvalidArgumentException;

/**
 * Collects and validates the block declarations exposed by SDK v2 extensions.
 *
 * The registry only returns metadata. Rendering remains the responsibility of
 * a trusted surface renderer, which must escape user-provided block content.
 */
final class ExtensionBlockRegistry
{
    /**
     * Return validated block declarations grouped by extension identifier.
     *
     * @param iterable<ExtensionInterface> $extensions
     * @return array<string, list<array<string, mixed>>>
     */
    public function all(iterable $extensions): array
    {
        $blocks = [];
        $globalIds = [];

        foreach ($extensions as $extension) {
            if (! $extension instanceof ExtensionInterface || ! $extension instanceof ExtensionBlocksInterface) {
                continue;
            }

            $extensionId = $extension->manifest()->id;
            $blocks[$extensionId] = [];

            foreach ($extension->blocks() as $definition) {
                $block = $this->validate($extensionId, $definition);
                $qualifiedId = $extensionId . '.' . $block['id'];

                if (isset($globalIds[$qualifiedId])) {
                    throw new InvalidArgumentException("Duplicate extension block identifier [{$qualifiedId}].");
                }

                $globalIds[$qualifiedId] = true;
                $blocks[$extensionId][] = [...$block, 'extension_id' => $extensionId, 'qualified_id' => $qualifiedId];
            }
        }

        return $blocks;
    }

    /**
     * Return the validated blocks for one extension.
     *
     * @return list<array<string, mixed>>
     */
    public function for(ExtensionInterface $extension): array
    {
        return $this->all([$extension])[$extension->manifest()->id] ?? [];
    }

    /**
     * Validate the public metadata contract before it reaches an API consumer.
     *
     * @param array<string, mixed> $definition
     * @return array<string, mixed>
     */
    private function validate(string $extensionId, array $definition): array
    {
        $id = $definition['id'] ?? null;
        $label = $definition['label'] ?? null;
        $surfaces = $definition['surfaces'] ?? ['admin'];
        $schema = $definition['schema'] ?? [];

        if (! is_string($id) || ! preg_match('/^[a-z][a-z0-9_-]*$/', $id)) {
            throw new InvalidArgumentException("Extension [{$extensionId}] declared a block with an invalid id.");
        }

        if (! is_string($label) || trim($label) === '') {
            throw new InvalidArgumentException("Extension [{$extensionId}] block [{$id}] must declare a label.");
        }

        if (
            ! is_array($surfaces) || $surfaces === [] || array_filter(
                $surfaces,
                static fn (mixed $surface): bool => ! is_string($surface) || ! in_array($surface, ['public', 'user', 'admin'], true),
            ) !== []
        ) {
            throw new InvalidArgumentException("Extension [{$extensionId}] block [{$id}] declared invalid surfaces.");
        }

        if (! is_array($schema)) {
            throw new InvalidArgumentException("Extension [{$extensionId}] block [{$id}] schema must be an array.");
        }

        return [
            ...$definition,
            'id' => $id,
            'label' => trim($label),
            'surfaces' => array_values(array_unique($surfaces)),
            'schema' => $schema,
        ];
    }
}
