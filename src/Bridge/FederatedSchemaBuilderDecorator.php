<?php
namespace Toppynl\ApiPlatformFederation\Bridge;

use ApiPlatform\GraphQl\Type\SchemaBuilderInterface;
use GraphQL\Type\Schema;
use Toppynl\GraphQLFederation\FederatedSchema;
use Toppynl\GraphQLFederation\FederatedSchemaBuilder;
use Toppynl\GraphQLFederation\ReferenceResolverRegistry;

class FederatedSchemaBuilderDecorator implements SchemaBuilderInterface
{
    public function __construct(
        private readonly SchemaBuilderInterface $inner,
        private readonly ReferenceResolverRegistry $registry,
        private readonly AttributeReferenceResolverLoader $loader,
    ) {}

    public function getSchema(): Schema
    {
        return $this->getFederatedSchema()->schema;
    }

    public function getFederatedSchema(): FederatedSchema
    {
        $schema = $this->inner->getSchema();
        $this->loader->load($this->buildTypeMap($schema));

        $builder = FederatedSchemaBuilder::from($schema)
            ->withRegistry($this->registry);

        // Register entity keys on the builder so the _Entity union and @key SDL
        // annotations are emitted for every type that was loaded with @FederationKey.
        // getEntityKeyMap() accumulates across all load() calls (including those made
        // before this decorator was constructed), so pre-populated loaders work too.
        foreach ($this->loader->getEntityKeyMap() as $typeName => $fields) {
            $builder->withEntityKey($typeName, $fields);
        }

        return $builder->build();
    }

    private function buildTypeMap(Schema $schema): array
    {
        // Returns an empty map by default; in production the DI compiler pass
        // populates this from API Platform's resource metadata.
        return [];
    }
}
