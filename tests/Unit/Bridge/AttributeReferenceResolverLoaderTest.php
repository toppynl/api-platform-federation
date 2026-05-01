<?php
namespace Toppynl\ApiPlatformFederation\Tests\Unit\Bridge;

use ApiPlatform\Metadata\ApiResource;
use PHPUnit\Framework\TestCase;
use Toppynl\ApiPlatformFederation\Attribute\FederationKey;
use Toppynl\ApiPlatformFederation\Bridge\AttributeReferenceResolverLoader;
use Toppynl\ApiPlatformFederation\Bridge\ResourceReferenceResolver;
use Toppynl\GraphQLFederation\ReferenceResolverRegistry;

#[ApiResource]
#[FederationKey(fields: 'id')]
class StubProduct
{
    public string $id   = '1';
    public string $name = 'Widget';
}

class NoKeyResource {}

class AttributeReferenceResolverLoaderTest extends TestCase
{
    public function test_registers_resolver_for_key_annotated_resource(): void
    {
        $resolver = $this->createMock(ResourceReferenceResolver::class);
        $registry = new ReferenceResolverRegistry();
        $loader   = new AttributeReferenceResolverLoader($resolver, $registry);

        $loader->load(['StubProduct' => ['class' => StubProduct::class]]);

        $this->assertTrue($registry->has('StubProduct'));
    }

    public function test_skips_resources_without_federation_key(): void
    {
        $resolver = $this->createMock(ResourceReferenceResolver::class);
        $registry = new ReferenceResolverRegistry();
        $loader   = new AttributeReferenceResolverLoader($resolver, $registry);

        $loader->load(['NoKeyResource' => ['class' => NoKeyResource::class]]);

        $this->assertFalse($registry->has('NoKeyResource'));
    }

    public function test_registered_resolver_delegates_to_resource_resolver(): void
    {
        $resolver = $this->createMock(ResourceReferenceResolver::class);
        $resolver->expects($this->once())
            ->method('resolve')
            ->with(StubProduct::class, ['__typename' => 'StubProduct', 'id' => '1'])
            ->willReturn(new StubProduct());

        $registry = new ReferenceResolverRegistry();
        $loader   = new AttributeReferenceResolverLoader($resolver, $registry);
        $loader->load(['StubProduct' => ['class' => StubProduct::class]]);

        $result = $registry->resolve('StubProduct', ['__typename' => 'StubProduct', 'id' => '1']);
        $this->assertInstanceOf(StubProduct::class, $result);
    }
}
