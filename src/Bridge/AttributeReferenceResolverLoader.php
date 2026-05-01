<?php
namespace Toppynl\ApiPlatformFederation\Bridge;

use Toppynl\ApiPlatformFederation\Attribute\FederationKey;
use Toppynl\GraphQLFederation\ReferenceResolverRegistry;

class AttributeReferenceResolverLoader
{
    /**
     * Accumulated map of typeName → key fields string for all types that have
     * been loaded with a @FederationKey attribute.  Used by
     * FederatedSchemaBuilderDecorator to register entity keys on the builder.
     *
     * @var array<string, string>
     */
    private array $entityKeyMap = [];

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
            /** @var FederationKey $key */
            $key = $keyAttrs[0]->newInstance();
            $this->entityKeyMap[$typeName] = $key->fields;

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
    }

    /**
     * Returns all typeName → key fields pairs accumulated across all load() calls.
     *
     * @return array<string, string>
     */
    public function getEntityKeyMap(): array
    {
        return $this->entityKeyMap;
    }
}
