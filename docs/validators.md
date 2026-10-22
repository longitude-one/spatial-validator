# Validators

## Generic validation

Call `$validator->validate($value, new Spatial())` with Symfony Validator and
`LongitudeOne\SpatialValidator\Validator\Constraints\Spatial`.

| Constraint | Description |
| --- | --- |
| [Spatial](../lib/Validator/Constraints/Spatial.php) · [Validator](../lib/Validator/Constraints/SpatialValidator.php) | Validates any spatial interface |

## Specialized validation

Point validates coordinates. Other specialized validators currently report an unavailable-validation warning.

| Constraint | Description |
| --- | --- |
| [BRepSolid](../lib/Validator/Constraints/BRepSolid.php) · [Validator](../lib/Validator/Constraints/BRepSolidValidator.php) | Reports unavailable BRepSolid validation |
| [Circle](../lib/Validator/Constraints/Circle.php) · [Validator](../lib/Validator/Constraints/CircleValidator.php) | Reports unavailable Circle validation |
| [CircularString](../lib/Validator/Constraints/CircularString.php) · [Validator](../lib/Validator/Constraints/CircularStringValidator.php) | Reports unavailable CircularString validation |
| [Clothoid](../lib/Validator/Constraints/Clothoid.php) · [Validator](../lib/Validator/Constraints/ClothoidValidator.php) | Reports unavailable Clothoid validation |
| [CompoundCurve](../lib/Validator/Constraints/CompoundCurve.php) · [Validator](../lib/Validator/Constraints/CompoundCurveValidator.php) | Reports unavailable CompoundCurve validation |
| [CompoundSurface](../lib/Validator/Constraints/CompoundSurface.php) · [Validator](../lib/Validator/Constraints/CompoundSurfaceValidator.php) | Reports unavailable CompoundSurface validation |
| [Curve](../lib/Validator/Constraints/Curve.php) · [Validator](../lib/Validator/Constraints/CurveValidator.php) | Reports unavailable Curve validation |
| [CurvePolygon](../lib/Validator/Constraints/CurvePolygon.php) · [Validator](../lib/Validator/Constraints/CurvePolygonValidator.php) | Reports unavailable CurvePolygon validation |
| [EllipticalCurve](../lib/Validator/Constraints/EllipticalCurve.php) · [Validator](../lib/Validator/Constraints/EllipticalCurveValidator.php) | Reports unavailable EllipticalCurve validation |
| [GeodesicString](../lib/Validator/Constraints/GeodesicString.php) · [Validator](../lib/Validator/Constraints/GeodesicStringValidator.php) | Reports unavailable GeodesicString validation |
| [Geometry](../lib/Validator/Constraints/Geometry.php) · [Validator](../lib/Validator/Constraints/GeometryValidator.php) | Reports unavailable Geometry validation |
| [GeometryCollection](../lib/Validator/Constraints/GeometryCollection.php) · [Validator](../lib/Validator/Constraints/GeometryCollectionValidator.php) | Reports unavailable GeometryCollection validation |
| [LineString](../lib/Validator/Constraints/LineString.php) · [Validator](../lib/Validator/Constraints/LineStringValidator.php) | Reports unavailable LineString validation |
| [MultiCurve](../lib/Validator/Constraints/MultiCurve.php) · [Validator](../lib/Validator/Constraints/MultiCurveValidator.php) | Reports unavailable MultiCurve validation |
| [MultiLineString](../lib/Validator/Constraints/MultiLineString.php) · [Validator](../lib/Validator/Constraints/MultiLineStringValidator.php) | Reports unavailable MultiLineString validation |
| [MultiPoint](../lib/Validator/Constraints/MultiPoint.php) · [Validator](../lib/Validator/Constraints/MultiPointValidator.php) | Reports unavailable MultiPoint validation |
| [MultiPolygon](../lib/Validator/Constraints/MultiPolygon.php) · [Validator](../lib/Validator/Constraints/MultiPolygonValidator.php) | Reports unavailable MultiPolygon validation |
| [MultiSurface](../lib/Validator/Constraints/MultiSurface.php) · [Validator](../lib/Validator/Constraints/MultiSurfaceValidator.php) | Reports unavailable MultiSurface validation |
| [NURBSCurve](../lib/Validator/Constraints/NURBSCurve.php) · [Validator](../lib/Validator/Constraints/NURBSCurveValidator.php) | Reports unavailable NURBSCurve validation |
| [Point](../lib/Validator/Constraints/Point.php) · [Validator](../lib/Validator/Constraints/PointValidator.php) | Validates a point |
| [Polygon](../lib/Validator/Constraints/Polygon.php) · [Validator](../lib/Validator/Constraints/PolygonValidator.php) | Reports unavailable Polygon validation |
| [PolyhedralSurface](../lib/Validator/Constraints/PolyhedralSurface.php) · [Validator](../lib/Validator/Constraints/PolyhedralSurfaceValidator.php) | Reports unavailable PolyhedralSurface validation |
| [Solid](../lib/Validator/Constraints/Solid.php) · [Validator](../lib/Validator/Constraints/SolidValidator.php) | Reports unavailable Solid validation |
| [SpiralCurve](../lib/Validator/Constraints/SpiralCurve.php) · [Validator](../lib/Validator/Constraints/SpiralCurveValidator.php) | Reports unavailable SpiralCurve validation |
| [Surface](../lib/Validator/Constraints/Surface.php) · [Validator](../lib/Validator/Constraints/SurfaceValidator.php) | Reports unavailable Surface validation |
| [TIN](../lib/Validator/Constraints/TIN.php) · [Validator](../lib/Validator/Constraints/TINValidator.php) | Reports unavailable TIN validation |
| [Triangle](../lib/Validator/Constraints/Triangle.php) · [Validator](../lib/Validator/Constraints/TriangleValidator.php) | Reports unavailable Triangle validation |

## Coordinate rules

These reusable constraints accept PointInterface values and preserve Symfony groups and payload.

| Constraint | Description |
| --- | --- |
| [CoordinateLayout](../lib/Validator/Constraints/CoordinateLayout.php) · [Validator](../lib/Validator/Constraints/CoordinateLayoutValidator.php) | Checks declared ordinates and EMPTY consistency |
| [FiniteCoordinates](../lib/Validator/Constraints/FiniteCoordinates.php) · [Validator](../lib/Validator/Constraints/FiniteCoordinatesValidator.php) | Checks finite values, including M |
| [GeographyBounds](../lib/Validator/Constraints/GeographyBounds.php) · [Validator](../lib/Validator/Constraints/GeographyBoundsValidator.php) | Checks Geography longitude and latitude bounds |
