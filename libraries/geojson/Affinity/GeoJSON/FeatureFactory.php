<?php

namespace Affinity\GeoJSON;

/**
 *
 */
abstract class FeatureFactory {
  /**
   *
   */
  abstract public function __construct($info);

  /**
   *
   */
  abstract public function geometry($item);

  /**
   *
   */
  public function properties($item) {
    return array();
  }

  /**
   *
   */
  public function id($item) {
    return NULL;
  }

  /**
   * Convert an object into a GeoPHP Geometry object.
   *
   * The following rules will dictate the conversion:
   *  - Multi-value geofields of different types will be reduced to a
   *    GEOMETRYCOLLECTION.
   *  - Multi-value geofields of the same 'simple type' (POINT, LINESTRING or
   *    POLYGON) will be reduced to the MULTI* equivalent.
   *  - GEOMETRYCOLLECTION's containing multiple values of only one 'simple type'
   *    will be reduced to the MULTI* equvalent.
   *  - GEOMETRYCOLLECTION's or MULTI* values containing only one geometry will be
   *    reduced to that geometery.
   *
   * @param Traversable $object
   *    An object that represents a geometry or a (traversable) collection of
   *    such objects.
   *
   * @param Callable $to_geom
   *    A callback that takes your object and returns a GeoPHP Geometry object.
   *
   * @return
   *    A GeoPHP Geometry object representing the $object value
   *    converted according to the above rules.
   */
  protected function createGeoPHPGeometry($object, $to_geom = NULL) {
    geophp_load();

    if (empty($object)) {
      return NULL;
    }

    if (!$to_geom) {
      $to_geom = function ($wkt) { 
        return \geoPHP::load($wkt, 'wkt');
      };
    }

    // TODO: This reflection sucks.
    if (is_array($object) || ($object instanceof \Traversable && $object instanceof \Countable && $object instanceof \ArrayAccess)) {
      foreach ($object as $delta => $value) {
        $geometry = $this->createGeoPHPGeometry($value, $to_geom);
        if ($geometry) {
          $geom[] = $geometry;
        }
      }
    }
    else {
      $geom = $to_geom($object);
    }

    return \geoPHP::geometryReduce($geom);
  }
}

