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
        return FederatedSchemaBuilder::from($schema)
            ->withRegistry($this->registry)
            ->build();
    }

    private function buildTypeMap(Schema $schema): array
    {
        // Returns an empty map by default; in production the DI compiler pass
        // populates this from API Platform's resource metadata.
        return [];
    }
}
