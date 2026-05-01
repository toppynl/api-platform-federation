<?php
namespace Toppynl\ApiPlatformFederation\Bridge;

use ApiPlatform\Metadata\Get;
use ApiPlatform\State\ProviderInterface;

class ResourceReferenceResolver
{
    public function __construct(private readonly ProviderInterface $provider) {}

    public function resolve(string $resourceClass, array $representation): mixed
    {
        $id = $representation['id'] ?? null;
        if ($id === null) {
            return null;
        }
        try {
            return $this->provider->provide(new Get(), ['id' => $id], ['resource_class' => $resourceClass]);
        } catch (\Throwable) {
            return null;
        }
    }
}
