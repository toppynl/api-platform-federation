<?php
namespace Toppynl\ApiPlatformFederation\Tests\Unit\Attribute;

use PHPUnit\Framework\TestCase;
use Toppynl\ApiPlatformFederation\Attribute\FederationKey;

class FederationKeyTest extends TestCase
{
    public function test_attribute_targets_class_and_is_repeatable(): void
    {
        $ref  = new \ReflectionClass(FederationKey::class);
        $attr = $ref->getAttributes(\Attribute::class)[0]->newInstance();
        $this->assertTrue((bool) ($attr->flags & \Attribute::TARGET_CLASS));
        $this->assertTrue((bool) ($attr->flags & \Attribute::IS_REPEATABLE));
    }

    public function test_stores_fields(): void
    {
        $key = new FederationKey(fields: 'id sku');
        $this->assertSame('id sku', $key->fields);
    }

    public function test_resolvable_defaults_to_true(): void
    {
        $key = new FederationKey(fields: 'id');
        $this->assertTrue($key->resolvable);
    }

    public function test_resolvable_can_be_set_to_false(): void
    {
        $key = new FederationKey(fields: 'id', resolvable: false);
        $this->assertFalse($key->resolvable);
    }
}
