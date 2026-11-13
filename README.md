# Spatial Validator

[![Release](https://img.shields.io/github/v/release/longitude-one/spatial-validator?include_prereleases&sort=semver&label=release)](https://github.com/longitude-one/spatial-validator/releases)
[![PHP](https://img.shields.io/packagist/php-v/longitude-one/spatial-validator?label=php)](https://packagist.org/packages/longitude-one/spatial-validator)
[![License](https://img.shields.io/packagist/l/longitude-one/spatial-validator)](https://github.com/longitude-one/spatial-validator/blob/main/LICENSE)
[![CI](https://github.com/longitude-one/spatial-validator/actions/workflows/ci.yml/badge.svg)](https://github.com/longitude-one/spatial-validator/actions/workflows/ci.yml)
[![Coverage](https://codecov.io/gh/longitude-one/spatial-validator/branch/main/graph/badge.svg)](https://codecov.io/gh/longitude-one/spatial-validator/tree/main)
[![Monthly downloads](https://img.shields.io/packagist/dm/longitude-one/spatial-validator)](https://packagist.org/packages/longitude-one/spatial-validator)
[![Documentation](https://img.shields.io/badge/docs-v1-blue)](https://www.longitude-one.fr/spatial-validator/v1/index.html)

Validate LongitudeOne spatial objects explicitly with Symfony Validator.
The library checks spatial objects supplied by spatial-types; it does not parse strings,
repair geometries or reproject coordinates.

## Installation

```sh
composer require longitude-one/spatial-validator:^1.0.0
```

## Usage

Use the `Spatial` constraint with Symfony Validator to dispatch by geometry type:

```php
use LongitudeOne\SpatialTypes\Types\Dimension2\LineString;
use LongitudeOne\SpatialValidator\Validator\Constraints\Spatial;
use Symfony\Component\Validator\Validation;

$line = new LineString([[0, 0], [1, 0], [0, 1], [0, 0]]);
$violations = Validation::createValidator()->validate($line, new Spatial());
```

Constraints and their validators live under `LongitudeOne\SpatialValidator\Validator\Constraints`.
`Spatial` delegates to one dedicated constraint per `GeometryTypeEnum` case.
You can also apply a specialized constraint directly, for example `Point` or `Triangle`.
Standard Symfony groups and payload are accepted; delegation preserves the validation root,
property path and active group. `Spatial` has no warning payload by default and does not copy
its payload to specialized constraints.

## Documentation

Read the [official documentation](https://www.longitude-one.fr/spatial-validator/v1/index.html).

See the [validator reference](docs/validators.md) for constraints and diagnostics,
and the [validation guide](docs/validation.md) for detailed behavior and limitations.

## Compatibility

Requires PHP 8.4 or later within PHP 8 and supports Symfony Validator 5.4, 6, 7 and 8.
Mutable and immutable spatial-types objects are validated through their interfaces.

## Input compatibility

Only PHP `null` is ignored. All strings, including empty strings and WKT, are rejected;
decode serialized geometry before validation. EMPTY is a spatial state and still receives validation.
Compose Symfony `NotNull` with `Spatial` when a value is required.

## Validation coverage

Implemented validators cover Point, LineString, CircularString, CompoundCurve, Polygon,
Triangle, GeometryCollection, MultiPoint, MultiLineString, MultiPolygon and PolyhedralSurface,
within their [documented limits](docs/validation.md).
Closure and simplicity are explicit constraints where applicable.

Other admissible types report unavailable validation as a violation with warning metadata.
Geography checks also report warnings where geodetic calculations are unavailable.
Warnings remain in Symfony's violation list and do not establish geometric validity.

## Contributing

See [Contributing](CONTRIBUTING.md) for development setup, checks and contribution guidelines.
