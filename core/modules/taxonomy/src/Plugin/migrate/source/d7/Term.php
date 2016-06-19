<?php

namespace Drupal\taxonomy\Plugin\migrate\source\d7;

use Drupal\migrate\Row;
use Drupal\migrate_drupal\Plugin\migrate\source\d7\FieldableEntity;

/**
 * Taxonomy term source from database.
 *
 * @todo Support term_relation, term_synonym table if possible.
 *
 * @MigrateSource(
 *   id = "d7_taxonomy_term",
 *   source_provider = "taxonomy"
 * )
 */
class Term extends FieldableEntity {

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
    $this->termDataTable = 'taxonomy_term_data';
    $this->termHierarchyTable = 'taxonomy_term_hierarchy';
    $this->vocabularyTable = 'taxonomy_vocabulary';

    $query = $this->select($this->termDataTable, 'td')
      ->fields('td')
      ->distinct()
      ->orderBy('tid');
    $query->leftJoin($this->vocabularyTable, 'tv', 'td.vid = tv.vid');
    $query->addField('tv', 'machine_name');

    if (isset($this->configuration['vocabulary'])) {
      $query->condition('td.vid', $this->configuration['vocabulary'], 'IN');
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
      'machine_name' => $this->t('Vocabulary machine name'),
      'name' => $this->t('The name of the term.'),
      'description' => $this->t('The term description.'),
      'weight' => $this->t('Weight'),
      'parent' => $this->t("The Drupal term IDs of the term's parents."),
      'format' => $this->t("Format of the term description."),
    );
    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    // Get Field API field values.
    foreach (array_keys($this->getFields('taxonomy_term', $row->getSourceProperty('machine_name'))) as $field) {
      $tid = $row->getSourceProperty('tid');
      $row->setSourceProperty($field, $this->getFieldValues('taxonomy_term', $field, $tid));
    }

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
