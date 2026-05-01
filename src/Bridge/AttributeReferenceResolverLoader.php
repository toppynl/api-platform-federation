<?php
namespace Toppynl\ApiPlatformFederation\Bridge;

use Toppynl\ApiPlatformFederation\Attribute\FederationKey;
use Toppynl\GraphQLFederation\ReferenceResolverRegistry;

class AttributeReferenceResolverLoader
{
    public function __construct(
        private readonly ResourceReferenceResolver $resolver,
        private readonly ReferenceResolverRegistry $registry,
    ) {}

    /**
     * @param array<string, array{class: string}> $typeMap typeName → ['class' => FQCN]
     */
    public function load(array $typeMap): void
    {
        foreach ($typeMap as $typeName => $meta) {
            $resourceClass = $meta['class'];
            if (!class_exists($resourceClass)) {
                continue;
            }
            $ref      = new \ReflectionClass($resourceClass);
            $keyAttrs = $ref->getAttributes(FederationKey::class);
            if (empty($keyAttrs)) {
                continue;
            }
            $resolver = $this->resolver;
            $this->registry->register(
                $typeName,
                fn (array $rep) => $resolver->resolve($resourceClass, $rep),
            );
        }
    }
}
