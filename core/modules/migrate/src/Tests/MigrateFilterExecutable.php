<?php

/**
 * @file
 * Contains \Drupal\migrate\Tests\MigrateFilterExecutable.
 */

namespace Drupal\migrate\Tests;

use Drupal\migrate\Entity\MigrationInterface;
use Drupal\migrate\MigrateExecutable;
use Drupal\migrate\MigrateMessageInterface;
use Drupal\migrate\MigrateSkipRowException;
use Drupal\migrate\Row;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * A MigrateExecutable that allows filtering the imported rows.
 */
class MigrateFilterExecutable extends MigrateExecutable {
  /**
   * Predicate for source rows to migrate.
   *
   * @var callback $filter
   */
  protected $filter;

  /**
   * {@inheritdoc}
   *
   * @param callback $filter
   *   Predicate for source rows to migrate.
   */
  public function __construct(MigrationInterface $migration, MigrateMessageInterface $message, EventDispatcherInterface $event_dispatcher = NULL, $filter = NULL) {
    parent::__construct($migration, $message, $event_dispatcher);
    $this->filter = $filter;
  }

  public function processRow(Row $row, array $process = NULL, $value = NULL) {
    if (!call_user_func($this->filter, $this->migration, $row)) {
      throw new MigrateSkipRowException("Row filtered", FALSE);
    }
    parent::processRow($row, $process, $value);
  }

}
