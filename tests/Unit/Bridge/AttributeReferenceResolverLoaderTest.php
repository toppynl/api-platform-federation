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

#[FederationKey(fields: 'id')]
#[FederationKey(fields: 'sku')]
class MultiKeyProduct {}

#[FederationKey(fields: 'id', resolvable: false)]
class NonResolvableStub {}

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

    public function test_collects_all_key_field_sets_for_multi_key_resource(): void
    {
        $resolver = $this->createMock(ResourceReferenceResolver::class);
        $registry = new ReferenceResolverRegistry();
        $loader   = new AttributeReferenceResolverLoader($resolver, $registry);

        $result = $loader->load(['MultiKeyProduct' => ['class' => MultiKeyProduct::class]]);

        $this->assertArrayHasKey('MultiKeyProduct', $result);
        $this->assertContains('id',  $result['MultiKeyProduct']['keyFieldSets']);
        $this->assertContains('sku', $result['MultiKeyProduct']['keyFieldSets']);
        $this->assertCount(2, $result['MultiKeyProduct']['keyFieldSets']);
    }

    public function test_does_not_register_resolver_for_non_resolvable_resource(): void
    {
        $resolver = $this->createMock(ResourceReferenceResolver::class);
        $registry = new ReferenceResolverRegistry();
        $loader   = new AttributeReferenceResolverLoader($resolver, $registry);

        $result = $loader->load(['NonResolvableStub' => ['class' => NonResolvableStub::class]]);

        $this->assertFalse($registry->has('NonResolvableStub'));
        $this->assertFalse($result['NonResolvableStub']['resolvable']);
    }
}
