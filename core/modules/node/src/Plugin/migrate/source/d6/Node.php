<?php

namespace Drupal\node\Plugin\migrate\source\d6;

use Drupal\migrate\Row;
use Drupal\migrate_drupal\Plugin\migrate\source\DrupalSqlBase;

/**
 * Drupal 6 node source from database.
 *
 * @MigrateSource(
 *   id = "d6_node"
 * )
 */
class Node extends DrupalSqlBase {

  /**
   * An expression for the translation set of a node.
   */
  const NODE_TRANSLATION_SET = 'CASE n.tnid WHEN 0 THEN n.nid ELSE n.tnid END';

  /**
   * The join options between the node and the node_revisions table.
   */
  const JOIN = 'n.vid = nr.vid';

  /**
   * The default filter format.
   *
   * @var string
   */
  protected $filterDefaultFormat;

  /**
   * Cached field and field instance definitions.
   *
   * @var array
   */
  protected $fieldInfo;

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = $this->translationQuery();

    $query->fields('n', array(
        'nid',
        'type',
        'language',
        'status',
        'created',
        'changed',
        'comment',
        'promote',
        'moderate',
        'sticky',
        'tnid',
        'translate',
      ))
      ->fields('nr', array(
        'title',
        'body',
        'teaser',
        'log',
        'timestamp',
        'format',
      ));
    $query->addField('n', 'uid', 'node_uid');
    $query->addField('nr', 'uid', 'revision_uid');

    // Whatever we claim this revision is, use the actual field from
    // node_revisions to get field values.
    $query->addField('nr', 'vid', 'vid_for_fields');

    if (isset($this->configuration['node_type'])) {
      $query->condition('n.type', $this->configuration['node_type']);
    }

    return $query;
  }

  /**
   * {@inheritdoc}
   */
  protected function initializeIterator() {
    $this->filterDefaultFormat = $this->variableGet('filter_default_format', '1');
    return parent::initializeIterator();
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    $fields = array(
      'nid' => $this->t('Node ID'),
      'type' => $this->t('Type'),
      'title' => $this->t('Title'),
      'body' => $this->t('Body'),
      'format' => $this->t('Format'),
      'teaser' => $this->t('Teaser'),
      'node_uid' => $this->t('Node authored by (uid)'),
      'revision_uid' => $this->t('Revision authored by (uid)'),
      'created' => $this->t('Created timestamp'),
      'changed' => $this->t('Modified timestamp'),
      'status' => $this->t('Published'),
      'promote' => $this->t('Promoted to front page'),
      'sticky' => $this->t('Sticky at top of lists'),
      'revision' => $this->t('Create new revision'),
      'language' => $this->t('Language (fr, en, ...)'),
      'tnid' => $this->t('The translation set id for this node'),
      'timestamp' => $this->t('The timestamp the latest revision of this node was created.'),
    );
    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    // format = 0 can happen when the body field is hidden. Set the format to 1
    // to avoid migration map issues (since the body field isn't used anyway).
    if ($row->getSourceProperty('format') === '0') {
      $row->setSourceProperty('format', $this->filterDefaultFormat);
    }

    if ($this->moduleExists('content') && $this->getModuleSchemaVersion('content') >= 6001) {
      foreach ($this->getFieldValues($row) as $field => $values) {
        $row->setSourceProperty($field, $values);
      }
    }

    // Use the same nid for translation sets.
    if ($tnid = $row->getSourceProperty('tnid')) {
      $row->setSourceProperty('nid', $tnid);
    }

    return parent::prepareRow($row);
  }

  /**
   * Gets CCK field values for a node.
   *
   * @param \Drupal\migrate\Row $node
   *   The node.
   *
   * @return array
   *   CCK field values, keyed by field name.
   */
  protected function getFieldValues(Row $node) {
    $values = [];
    foreach ($this->getFieldInfo($node->getSourceProperty('type')) as $field => $info) {
      $values[$field] = $this->getCckData($info, $node);
    }
    return $values;
  }

  /**
   * Gets CCK field and instance definitions from the database.
   *
   * @param string $node_type
   *   The node type for which to get field info.
   *
   * @return array
   *   Field and instance information for the node type, keyed by field name.
   */
  protected function getFieldInfo($node_type) {
    if (!isset($this->fieldInfo)) {
      $this->fieldInfo = [];

      // Query the database directly for all CCK field info.
      $query = $this->select('content_node_field_instance', 'cnfi');
      $query->join('content_node_field', 'cnf', 'cnf.field_name = cnfi.field_name');
      $query->fields('cnfi');
      $query->fields('cnf');

      foreach ($query->execute() as $field) {
        $this->fieldInfo[ $field['type_name'] ][ $field['field_name'] ] = $field;
      }

      foreach ($this->fieldInfo as $type => $fields) {
        foreach ($fields as $field => $info) {
          foreach ($info as $property => $value) {
            if ($property == 'db_columns' || preg_match('/_settings$/', $property)) {
              $this->fieldInfo[$type][$field][$property] = unserialize($value);
            }
          }
        }
      }
    }

    return isset($this->fieldInfo[$node_type]) ? $this->fieldInfo[$node_type] : [];
  }

  /**
   * Retrieves raw CCK field data for a node.
   *
   * @param array $field
   *   A field and instance definition from getFieldInfo().
   * @param \Drupal\migrate\Row $node
   *   The node.
   *
   * @return array
   *   The field values, keyed by delta.
   */
  protected function getCckData(array $field, Row $node) {
    $field_table = 'content_' . $field['field_name'];
    $node_table = 'content_type_' . $node->getSourceProperty('type');

    /** @var \Drupal\Core\Database\Schema $db */
    $db = $this->getDatabase()->schema();

    if ($db->tableExists($field_table)) {
      $query = $this->select($field_table, 't');

      // If the delta column does not exist, add it as an expression to
      // normalize the query results.
      if ($db->fieldExists($field_table, 'delta')) {
        $query->addField('t', 'delta');
      }
      else {
        $query->addExpression(0, 'delta');
      }
    }
    elseif ($db->tableExists($node_table)) {
      $query = $this->select($node_table, 't');

      // Every row should have a delta of 0.
      $query->addExpression(0, 'delta');
    }

    if (isset($query)) {
      $columns = array_keys($field['db_columns']);

      // Add every column in the field's schema.
      foreach ($columns as $column) {
        $query->addField('t', $field['field_name'] . '_' . $column, $column);
      }

      return $query
        // This call to isNotNull() is a kludge which relies on the convention
        // that CCK field schemas usually define their most important
        // column first. A better way would be to allow cckfield plugins to
        // alter the query directly before it's run, but this will do for
        // the time being.
        ->isNotNull($field['field_name'] . '_' . $columns[0])
        ->condition('nid', $node->getSourceProperty('nid'))
        ->condition('vid', $node->getSourceProperty('vid_for_fields'))
        ->execute()
        ->fetchAllAssoc('delta');
    }
    else {
      return [];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    $ids['nid']['type'] = 'integer';
    $ids['nid']['alias'] = 'n';
    $ids['language']['type'] = 'string';
    $ids['language']['alias'] = 'n';
    return $ids;
  }

  /**
   * Build a query to get the maximum vid of each translation set.
   *
   * @return \Drupal\Core\Database\Query\SelectInterface
   *   The generated query.
   */
  protected function maxVidQuery() {
    $subquery = $this->select('node', 'n');
    $subquery->addExpression(self::NODE_TRANSLATION_SET, 'translation_set');
    $subquery->groupBy('translation_set');
    $subquery->addExpression('MAX(vid)', 'vid');
    return $subquery;
  }

  /**
   * Build a query that yields the rows we want to migrate.
   *
   * It should have a node table 'n', and a node_revision table 'nr'.
   *
   * @return \Drupal\Core\Database\Query\SelectInterface
   *   The generated query.
   */
  protected function translationQuery() {
    $query = $this->select('node_revisions', 'nr');
    $query->innerJoin('node', 'n', static::JOIN);

    // Claim our vid is the maximum vid of our translation set.
    // Otherwise we generate translations of the same node with different
    // revisions, which confuses Drupal.
    $query->join($this->maxVidQuery(), 'max_vid',
      'max_vid.translation_set IN (n.nid, n.tnid)');
    $query->fields('max_vid', ['vid']);

    return $query;
  }
}
