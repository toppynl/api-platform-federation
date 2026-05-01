<?php
namespace Toppynl\ApiPlatformFederation\Tests\Unit\Bridge;

use ApiPlatform\GraphQl\Type\SchemaBuilderInterface;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Schema;
use PHPUnit\Framework\TestCase;
use Toppynl\ApiPlatformFederation\Bridge\AttributeReferenceResolverLoader;
use Toppynl\ApiPlatformFederation\Bridge\FederatedSchemaBuilderDecorator;
use Toppynl\GraphQLFederation\FederatedSchema;
use Toppynl\GraphQLFederation\ReferenceResolverRegistry;

class FederatedSchemaBuilderDecoratorTest extends TestCase
{
    private Schema $baseSchema;
    private ObjectType $productType;

    protected function setUp(): void
    {
        $this->productType = new ObjectType([
            'name'   => 'Product',
            'fields' => ['id' => Type::string(), 'name' => Type::string()],
        ]);
        $this->baseSchema = new Schema([
            'query' => new ObjectType([
                'name'   => 'Query',
                'fields' => ['product' => $this->productType],
            ]),
        ]);
    }

    public function test_getSchema_returns_schema_with_service_field(): void
    {
        $inner = $this->createMock(SchemaBuilderInterface::class);
        $inner->method('getSchema')->willReturn($this->baseSchema);

        $registry = new ReferenceResolverRegistry();
        $registry->register('Product', fn ($rep) => ['id' => $rep['id'], 'name' => 'Widget']);

        $loader = $this->createMock(AttributeReferenceResolverLoader::class);

        $decorator = new FederatedSchemaBuilderDecorator($inner, $registry, $loader);
        $schema    = $decorator->getSchema();

        $this->assertInstanceOf(Schema::class, $schema);
        $this->assertTrue($schema->getQueryType()->hasField('_service'));
        $this->assertTrue($schema->getQueryType()->hasField('_entities'));
    }

    public function test_getFederatedSchema_returns_FederatedSchema_instance(): void
    {
        $inner = $this->createMock(SchemaBuilderInterface::class);
        $inner->method('getSchema')->willReturn($this->baseSchema);

        $registry = new ReferenceResolverRegistry();
        $registry->register('Product', fn ($rep) => null);

        $loader = $this->createMock(AttributeReferenceResolverLoader::class);

        $decorator  = new FederatedSchemaBuilderDecorator($inner, $registry, $loader);
        $fedSchema  = $decorator->getFederatedSchema();

        $this->assertInstanceOf(FederatedSchema::class, $fedSchema);
    }
}
