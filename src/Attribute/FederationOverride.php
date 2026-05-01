<?php
namespace Toppynl\ApiPlatformFederation\Attribute;

#[\Attribute(\Attribute::TARGET_PROPERTY)]
final class FederationOverride
{
    public function __construct(public readonly string $from) {}
}
