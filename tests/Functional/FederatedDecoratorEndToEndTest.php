<?php
namespace Toppynl\ApiPlatformFederation\Tests\Functional;

use ApiPlatform\GraphQl\Type\SchemaBuilderInterface;
use GraphQL\GraphQL;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Schema;
use PHPUnit\Framework\TestCase;
use Toppynl\ApiPlatformFederation\Attribute\FederationKey;
use Toppynl\ApiPlatformFederation\Bridge\AttributeReferenceResolverLoader;
use Toppynl\ApiPlatformFederation\Bridge\FederatedSchemaBuilderDecorator;
use Toppynl\ApiPlatformFederation\Bridge\ResourceReferenceResolver;
use Toppynl\GraphQLFederation\ReferenceResolverRegistry;

#[FederationKey(fields: 'id')]
class FunctionalProduct
{
    public string $id   = '42';
    public string $name = 'Functional Widget';
}

class FakeSchemBuilder implements SchemaBuilderInterface
{
    public function __construct(private Schema $schema) {}
    public function getSchema(): Schema { return $this->schema; }
}

class FederatedDecoratorEndToEndTest extends TestCase
{
    private Schema $baseSchema;

    protected function setUp(): void
    {
        $productType = new ObjectType([
            'name'   => 'FunctionalProduct',
            'fields' => [
                'id'   => Type::string(),
                'name' => Type::string(),
            ],
        ]);
        $this->baseSchema = new Schema([
            'query' => new ObjectType([
                'name'   => 'Query',
                'fields' => ['functionalProduct' => $productType],
            ]),
        ]);
    }

    public function test_service_sdl_contains_key_directive(): void
    {
        $decorator = $this->buildDecorator();
        $result    = GraphQL::executeQuery($decorator->getSchema(), '{ _service { sdl } }');

        $this->assertEmpty($result->errors);
        $sdl = $result->data['_service']['sdl'];
        $this->assertIsString($sdl);
        $this->assertStringContainsString('@key(fields: "id")', $sdl);
        $this->assertStringContainsString('FunctionalProduct', $sdl);
    }

    public function test_entities_resolves_representation(): void
    {
        $decorator = $this->buildDecorator();
        $result    = GraphQL::executeQuery(
            $decorator->getSchema(),
            '{ _entities(representations: [{__typename: "FunctionalProduct", id: "42"}]) { ... on FunctionalProduct { id name } } }',
        );

        $this->assertEmpty($result->errors);
        $this->assertSame('42', $result->data['_entities'][0]['id']);
        $this->assertSame('Functional Widget', $result->data['_entities'][0]['name']);
    }

    public function test_service_sdl_does_not_leak_infrastructure_types(): void
    {
        $decorator = $this->buildDecorator();
        $result    = GraphQL::executeQuery($decorator->getSchema(), '{ _service { sdl } }');

        $sdl = $result->data['_service']['sdl'];
        $this->assertStringNotContainsString('scalar _Any', $sdl);
        $this->assertStringNotContainsString('union _Entity', $sdl);
        $this->assertStringNotContainsString('type _Service', $sdl);
    }

    private function buildDecorator(): FederatedSchemaBuilderDecorator
    {
        $registry = new ReferenceResolverRegistry();
        $registry->register('FunctionalProduct', fn ($rep) => [
            '__typename' => 'FunctionalProduct',
            'id'   => $rep['id'],
            'name' => 'Functional Widget',
        ]);

        $resourceResolver = $this->createMock(ResourceReferenceResolver::class);
        $loader = new AttributeReferenceResolverLoader($resourceResolver, $registry);

        // Load type map so the decorator knows FunctionalProduct has @FederationKey
        $loader->load(['FunctionalProduct' => ['class' => FunctionalProduct::class]]);

        $inner = new FakeSchemBuilder($this->baseSchema);

        return new FederatedSchemaBuilderDecorator($inner, $registry, $loader);
    }
}
