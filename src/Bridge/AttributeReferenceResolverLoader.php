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
     * @return array<string, array{keyFieldSets: string[], resolvable: bool}>
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

            $keyFieldSets = [];
            $resolvable   = true;
            foreach ($keyAttrs as $i => $attrRef) {
                /** @var FederationKey $attr */
                $attr = $attrRef->newInstance();
                $keyFieldSets[] = $attr->fields;
                if ($i === 0) {
                    $resolvable = $attr->resolvable;
                }
            }

            $result[$typeName] = ['keyFieldSets' => $keyFieldSets, 'resolvable' => $resolvable];

            // Only register if resolvable and not already present — allows callers to
            // pre-register custom resolvers before load() is called.
            if ($resolvable && !$this->registry->has($typeName)) {
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
