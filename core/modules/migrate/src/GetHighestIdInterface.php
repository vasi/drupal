<?php

namespace Drupal\migrate;


interface GetHighestIdInterface {

  /**
   * Gets the highest value of an ID.
   *
   * The semantics of this may vary depending on context, but examples are:
   *   - The highest ID node that has been migrated.
   *   - The highest ID node that exists on the site.
   *
   * @return int
   *   The highest ID value found. If no IDs at all are found, or if the
   *   concept of a highest ID is not meaningful, zero should be returned.
   */
  public function getHighestId();

}
