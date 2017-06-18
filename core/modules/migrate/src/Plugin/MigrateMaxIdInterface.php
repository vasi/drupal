<?php

namespace Drupal\migrate\Plugin;


interface MigrateMaxIdInterface {

  /**
   * Gets the highest destination ID value from an ID map.
   *
   * @param string $field
   *   The destination field for which to get the highest ID.
   *
   * @return int
   *   The highest ID value found. If no IDs at all are found, or if the
   *   concept of a highest ID is not meaningful, zero should be returned.
   */
  public function getMaxId($field);

}
