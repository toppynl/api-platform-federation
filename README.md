# toppynl/api-platform-federation

GraphQL Federation v2 bridge for API Platform v3 — as a Symfony bundle.

[![CI](https://github.com/toppynl/api-platform-federation/actions/workflows/ci.yml/badge.svg)](https://github.com/toppynl/api-platform-federation/actions/workflows/ci.yml)

## What it does

This zero-config Symfony bundle decorates API Platform's `SchemaBuilderInterface` to inject Federation v2 infrastructure (`_service`, `_entities`, `@key` directives) into the composed GraphQL schema. Mark any API Platform resource as a federation entity with a single `#[FederationKey]` attribute — no YAML, no XML, no extra wiring required. Reference resolution delegates to API Platform's own state providers by default, and can be overridden per type.

## Requirements

| Dependency | Version |
|---|---|
| PHP | `^8.1` |
| `api-platform/core` | `^3.0` |
| `symfony/framework-bundle` | `^6.4 \|\| ^7.0` |
| `toppynl/graphql-federation` | `^1.0` |

## Installation

```bash
composer require toppynl/api-platform-federation
```

Register the bundle in `config/bundles.php`:

```php
Toppynl\ApiPlatformFederation\ToppynlApiPlatformFederationBundle::class => ['all' => true],
```

## Quick start

```php
use ApiPlatform\Metadata\ApiResource;
use Toppynl\ApiPlatformFederation\Attribute\FederationKey;

#[ApiResource]
#[FederationKey(fields: 'id')]
class Product
{
    public string $id;
    public string $name;
}
```

That's it — `_service`, `_entities`, and `@key(fields: "id")` in the SDL are injected automatically.

## Available attributes

| Attribute | Applies to | Purpose |
|---|---|---|
| `#[FederationKey(fields: 'id')]` | class | Mark as resolvable entity key |
| `#[FederationKey(fields: 'id', resolvable: false)]` | class | Reference-only stub (no resolver registered) |
| `#[FederationExternal]` | class / property | Mark as externally owned |
| `#[FederationShareable]` | class / property | Allow field sharing across subgraphs |
| `#[FederationInaccessible]` | class / property | Hide from composed schema |
| `#[FederationOverride(from: 'other-subgraph')]` | property | Override field from another subgraph |

## Multiple keys

A type can expose more than one key field set by stacking `#[FederationKey]` — all instances must agree on `resolvable`:

```php
#[ApiResource]
#[FederationKey(fields: 'id')]
#[FederationKey(fields: 'sku')]
class Product
{
    public string $id;
    public string $sku;
    public string $name;
}
```

Both keys are registered; the gateway may use either to resolve a `Product` reference.

## Custom reference resolvers

By default the bundle resolves `_entities` lookups through API Platform's state provider. To override this for a specific type, pre-register a callable in `ReferenceResolverRegistry` before the schema is built — the loader skips types that already have a resolver:

```php
use Toppynl\GraphQLFederation\ReferenceResolverRegistry;

class ProductReferenceResolverRegistrar
{
    public function __construct(private ReferenceResolverRegistry $registry) {}

    public function boot(): void
    {
        $this->registry->register('Product', function (array $rep): mixed {
            return $this->productRepo->findBySku($rep['sku']);
        });
    }
}
```

## How it works

- `FederatedSchemaBuilderDecorator` wraps API Platform's `SchemaBuilderInterface`, calls the inner builder to obtain the base schema, then hands it off to `FederatedSchemaBuilder` from `toppynl/graphql-federation`.
- `AttributeReferenceResolverLoader` reads `#[FederationKey]` attributes via reflection on each mapped resource class, collecting key field sets and the shared `resolvable` flag.
- `FederatedSchemaBuilder` (core library) splices `_service`, `_entities`, and all `@key` / `@external` / `@shareable` / `@inaccessible` / `@override` directives into the final schema. See [toppynl/graphql-federation](https://github.com/toppynl/graphql-federation) for the underlying mechanics.

## License

MIT
