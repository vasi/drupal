<?php

namespace Drupal\migrate;

use Drupal\migrate\Plugin\MigrateIdAuditInterface;
use Drupal\migrate\Plugin\MigrationInterface;

/**
 * Audits migrations for potential ID conflicts.
 */
class MigrateIdAuditor {

  /**
   * Audit a set of migrations for ID conflicts.
   *
   * @param \Drupal\migrate\Plugin\MigrationInterface[] $migrations
   *   The migrations to audit.
   *
   * @return string[]
   *   The entity type IDs of migrated content that may have problematic
   *   IDs. If no problems are found, an empty array will be returned.
   */
  public function auditIds(array $migrations) {
    $ret = [];
    foreach ($migrations as $migration) {
      $destination = $migration->getDestinationPlugin();
      if ($destination instanceof MigrateIdAuditInterface) {
        if ($destination->unsafeIdsExist($migration->getIdMap())) {
          $ret[$destination->entityTypeId()] = TRUE;
        }
      }
    }
    return array_keys($ret);
  }

}
