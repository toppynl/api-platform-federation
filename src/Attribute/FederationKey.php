<?php
namespace Toppynl\ApiPlatformFederation\Attribute;

#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::IS_REPEATABLE)]
final class FederationKey
{
    public function __construct(
        public readonly string $fields,
        public readonly bool $resolvable = true,
    ) {}
}
