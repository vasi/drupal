<?php

namespace Drupal\Tests\taxonomy\Unit\Migrate\d7;

use Drupal\Tests\migrate\Unit\MigrateSqlSourceTestCase;

/**
 * Base class for taxonomy term source unit tests.
 */
abstract class TermTestBase extends MigrateSqlSourceTestCase {

  const PLUGIN_CLASS = 'Drupal\taxonomy\Plugin\migrate\source\d7\Term';

  protected $migrationConfiguration = array(
    'id' => 'test',
    'highWaterProperty' => array('field' => 'test'),
    'source' => array(
      'plugin' => 'd7_taxonomy_term',
    ),
  );

  protected $expectedResults = array(
    array(
      'tid' => 1,
      'vid' => 5,
      'machine_name' => 'tags',
      'name' => 'name value 1',
      'description' => 'description value 1',
      'weight' => 0,
      'parent' => array(0),
    ),
    array(
      'tid' => 2,
      'vid' => 6,
      'machine_name' => 'categories',
      'name' => 'name value 2',
      'description' => 'description value 2',
      'weight' => 0,
      'parent' => array(0),
    ),
    array(
      'tid' => 3,
      'vid' => 6,
      'machine_name' => 'categories',
      'name' => 'name value 3',
      'description' => 'description value 3',
      'weight' => 0,
      'parent' => array(0),
    ),
    array(
      'tid' => 4,
      'vid' => 5,
      'machine_name' => 'tags',
      'name' => 'name value 4',
      'description' => 'description value 4',
      'weight' => 1,
      'parent' => array(1),
    ),
    array(
      'tid' => 5,
      'vid' => 6,
      'machine_name' => 'categories',
      'name' => 'name value 5',
      'description' => 'description value 5',
      'weight' => 1,
      'parent' => array(2),
    ),
    array(
      'tid' => 6,
      'vid' => 6,
      'machine_name' => 'categories',
      'name' => 'name value 6',
      'description' => 'description value 6',
      'weight' => 0,
      'parent' => array(3, 2),
    ),
  );

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    foreach ($this->expectedResults as $k => $row) {
      foreach ($row['parent'] as $parent) {
        $this->databaseContents['taxonomy_term_hierarchy'][] = [
          'tid' => $row['tid'],
          'parent' => $parent,
        ];
      }
      unset($row['parent']);
      $this->databaseContents['taxonomy_term_data'][$k] = $row;
      $this->databaseContents['taxonomy_vocabulary'][$row['vid']] = [
        'vid' => $row['vid'],
        'machine_name' => $row['machine_name'],
      ];
      $this->databaseContents['field_config_instance'][$row['machine_name']] = [
        'field_name' => 'field_term_field',
        'entity_type' => 'taxonomy_term',
        'bundle' => $row['machine_name'],
        'deleted' => 0,
      ];
      $this->databaseContents['field_data_field_term_field'][$row['machine_name']] = [
        'entity_type' => 'taxonomy_term',
        'bundle' => $row['machine_name'],
        'deleted' => 0,
        'entity_id' => 1,
        'delta' => 0,
      ];
    }

    parent::setUp();
  }

}
