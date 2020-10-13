<?php

namespace Affinity\GeoJSON;

/**
 *
 */
interface CacheInterface {

  /**
   *
   */
  public function get($key);

  /**
   *
   */
  public function set($key, $value);

}
