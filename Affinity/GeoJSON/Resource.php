<?php

namespace Affinity\GeoJSON;

class Resource {

  /**
   *
   */
  public function __construct($info, $data = NULL) {
    $class = new \ReflectionClass($info['factory']);
    if (!$class->isSubclassOf('Affinity\GeoJSON\FeatureFactory')) {
      throw new \RuntimeException('Invalid feature factory.');
    }

    $this->factory = $class->newInstance($info['factory args']);

    $this->data = $data;
    $this->processed = FALSE;
    $this->features = array();
    $this->route = $info['route'];
    $this->properties = isset($info['properties callback']) ? $info['properties callback'] : NULL;
  }

  /**
   *
   */
  protected function process() {
    $this->processed = TRUE;
    $this->features = array();

    if (is_callable($this->data)) {
      $this->data = call_user_func($this->data);
    }

    foreach ($this->data as $key => $item) {
      $this->geometries[$key] = $this->factory->geometry($item);
      $this->properties[$key] = $this->factory->properties($item);
    }
  }

  /**
   *
   */
  public function uri() {
    $args = func_get_args();
    $pieces = explode('/', $this->route);
    foreach ($pieces as $index => $piece) {
      if ($piece[0] === '%') $pieces[$index] = array_shift($args);
    }
    return implode($pieces, '/');
  }

  /**
   *
   */
  public function geojson($reset = FALSE) {
    if (!$this->processed || $reset) {
      $this->process();
    }

    if (!empty($this->data) && empty($this->features) || $reset) {
      foreach ($this->data as $key => $item) {
        $geom = $this->geometries[$key];
        $properties = $this->properties[$key];
        $this->features[] = $this->feature($geom, $properties);
      }
    }

    if (count($this->features) === 1) {
      return array_shift($this->features);
    }

    return $this->featureCollection($this->features);
  }

  public function featureCollection(array $features) {
    $collection = new \stdclass();
    $collection->type = 'FeatureCollection';
    $collection->features = $features;
    return $collection;
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
  public function feature(\Geometry $geometry, Array $properties = array()) {
    $feature = new \stdclass();
    $feature->type = 'Feature';
    $feature->geometry = $geometry->out('json', TRUE);
    $feature->properties = $properties;
    return $feature;
  }

}
