<?php

namespace Drupal\migrate_drupal\Plugin\migrate\process;

use Drupal\migrate\Plugin\migrate\process\Migration;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;

/**
 * Looks up the parent of an entity.
 *
 * Like 'migration', but if it sees the value zero, just returns the value of
 * 'none'.
 *
 * @MigrateProcessPlugin(
 *   id = "entity_parent"
 * )
 */
class EntityParent extends Migration {
  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    if (is_numeric($value) && $value == 0) {
      return $this->configuration['none'];
    }

    return parent::transform($value, $migrate_executable, $row, $destination_property);
  }

}
