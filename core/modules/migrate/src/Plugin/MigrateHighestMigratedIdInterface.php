<?php

namespace Drupal\migrate\Plugin;


interface MigrateHighestMigratedIdInterface {

  /**
   * Gets the highest ID value that has been migrated.
   *
   * @return int
   *   The highest ID value found. If no IDs at all are found, or if the
   *   concept of a highest ID is not meaningful, zero should be returned.
   */
  public function highestMigratedId();

}
