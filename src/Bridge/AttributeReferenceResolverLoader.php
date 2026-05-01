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
     * @return array<string, string> typeName → key fields string for each type with a @FederationKey attribute
     */
    public function load(array $typeMap): array
    {
        $result = [];

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
            /** @var FederationKey $keyAttr */
            $keyAttr = $keyAttrs[0]->newInstance();
            $result[$typeName] = $keyAttr->fields;

            // Only register if not already present — allows callers to
            // pre-register custom resolvers before load() is called.
            if (!$this->registry->has($typeName)) {
                $resolver = $this->resolver;
                $this->registry->register(
                    $typeName,
                    fn (array $rep) => $resolver->resolve($resourceClass, $rep),
                );
            }
        }

        return $result;
    }
}
