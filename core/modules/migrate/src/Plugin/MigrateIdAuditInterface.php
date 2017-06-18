<?php

namespace Drupal\migrate\Plugin;

/**
 * Should be implemented by destinations that are able to audit whether
 * they may encounter unsafe ID mappings.
 */
interface MigrateIdAuditInterface {

  /**
   * Check whether unsafe IDs exist that should inhibit migration.
   *
   * @param MigrateIdMapInterface $idMap
   *   The ID map for this migration.
   *
   * @return bool
   *   Whether unsafe IDs exist.
   */
  public function unsafeIdsExist(MigrateIdMapInterface $idMap);

  /**
   * Get the type ID of the entities this destination creates.
   *
   * @return string
   *   The type ID, eg: 'node'.
   */
  public function entityTypeId();

}
