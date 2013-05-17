<?php

namespace Affinity\GeoJSON;

/**
 * Example of a 'simple' GeoJSON Feature factory.
 *
 * @return
 *    A GeoJSON Feature object (see feature()).
 */
function simple_geojson_feature_factory($wkt) {
  $geom = create_geophp_geometry($wkt);
  return is_null($geom) ? NULL : feature($geom);
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
function create_geophp_geometry($object, $to_geom = NULL) {
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
      $geometry = create_geophp_geometry($value, $to_geom);
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

/**
 * Creates a GeoJSON Feature.
 *
 * @param Geometry $geometry
 *    A GeoPHP Geometry object representing the geo data of the object.
 *
 * @param Array $properties
 *    An array of properties (key, value) to be included in the feature.
 *
 * @return
 *    An array structured as a GeoJSON Feature.
 */
function feature(\Geometry $geometry, Array $properties = array()) {
  $feature = new \stdclass();
  $feature->type = 'Feature';
  $feature->geometry = json_decode($geometry->out('json'));
  $feature->properties = $properties;
  return $feature;
}

/**
 * Creates a GeoJSON FeatureCollection from a collection of objects
 * representing geometries.
 *
 * @param Traversable $items
 *    Collection of objects representing geometries. Each one will be passed
 *    into $feature_factory.
 *
 * @param Callable $feature_factory
 *    A callable that takes an object representing a geometry and returns a
 *    GeoJSON Feature object (see feature()).
 *
 * @return
 *    An array structured as a GeoJSON FeatureCollection.
 */
function feature_collection($items, $feature_factory = NULL) {
  $features = array();

  if (is_null($feature_factory)) {
    $feature_factory = '\Affinity\GeoJSON\simple_geojson_feature_factory';
  }

  foreach ($items as $item) {
    $feature = $feature_factory($item);

    if ($feature) {
      $features[] = $feature;
    }
  }

  $feature = new \stdclass();
  $feature->type = 'FeatureCollection';
  $feature->features = $features;

  return $feature;
}
