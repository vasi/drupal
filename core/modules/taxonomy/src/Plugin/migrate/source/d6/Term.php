<?php

namespace Drupal\taxonomy\Plugin\migrate\source\d6;

use Drupal\migrate\Row;
use Drupal\migrate_drupal\Plugin\migrate\source\DrupalSqlBase;

/**
 * Taxonomy term source from database.
 *
 * @todo Support term_relation, term_synonym table if possible.
 *
 * @MigrateSource(
 *   id = "d6_taxonomy_term",
 *   source_provider = "taxonomy"
 * )
 */
class Term extends DrupalSqlBase {

  /**
   * Name of the term data table.
   *
   * @var string
   */
  protected $termDataTable;

  /**
   * Name of the term hierarchy table.
   *
   * @var string
   */
  protected $termHierarchyTable;

  /**
   * {@inheritdoc}
   */
  public function query() {
    $this->termDataTable = 'term_data';
    $this->termHierarchyTable = 'term_hierarchy';

    $query = $this->select($this->termDataTable, 'td')
      ->fields('td')
      ->distinct()
      ->orderBy('tid');

    if (isset($this->configuration['vocabulary'])) {
      $query->condition('vid', $this->configuration['vocabulary'], 'IN');
    }

    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    $fields = array(
      'tid' => $this->t('The term ID.'),
      'vid' => $this->t('Existing term VID'),
      'name' => $this->t('The name of the term.'),
      'description' => $this->t('The term description.'),
      'weight' => $this->t('Weight'),
      'parent' => $this->t("The Drupal term IDs of the term's parents."),
    );
    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    // Find parents for this row.
    $parents = $this->select($this->termHierarchyTable, 'th')
      ->fields('th', array('parent', 'tid'))
      ->condition('tid', $row->getSourceProperty('tid'))
      ->execute()
      ->fetchCol();
    $row->setSourceProperty('parent', $parents);

    return parent::prepareRow($row);
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    $ids['tid']['type'] = 'integer';
    return $ids;
  }

}
