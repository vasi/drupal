<?php

namespace Drupal\node\Plugin\migrate\source\d6;

/**
 * Drupal 6 node revision source from database.
 *
 * @MigrateSource(
 *   id = "d6_node_revision"
 * )
 */
class NodeRevision extends Node {

  /**
   * The join options between a node table and its translations.
   */
  const NODE_TRANSLATION_JOIN = '(n.tnid <> 0 AND n.tnid = nt.tnid) OR n.nid = nt.nid';

  /**
   * {@inheritdoc}
   */
  public function fields() {
    // Use all the node fields plus the vid that identifies the version.
    return parent::fields() + array(
      'log' => $this->t('Revision Log message'),
      'timestamp' => $this->t('Revision timestamp'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    $ids['vid']['type'] = 'integer';
    $ids['vid']['alias'] = 'tvids';
    $ids['language']['type'] = 'string';
    $ids['language']['alias'] = 'tvids';
    return $ids;
  }

  /**
   * Build a query to turn each revision into multiple translation rows.
   *
   * For each D6 revision, generate a row for each translation that existed
   * at that time.
   *
   * Eg: If we have:
   *   | tnid | nid | vid | language |
   *   |    1 |   1 |   1 |       en |
   *   |    1 |   2 |   2 |       fr |
   *   |    1 |   3 |   3 |       de |
   *   |    1 |   1 |   4 |       en |
   *
   *
   * Then we generate the following rows:
   *   | vid | language | tvid |
   *   |   1 |       en |    1 |
   *   |   2 |       en |    1 |
   *   |   2 |       fr |    2 |
   *   |   3 |       en |    1 |
   *   |   3 |       fr |    2 |
   *   |   3 |       de |    3 |
   *   |   4 |       en |    4 |
   *   |   4 |       fr |    2 |
   *   |   4 |       de |    3 |
   *
   * @return \Drupal\Core\Database\Query\SelectInterface
   *   The generated query.
   */
  protected function translationRevisionsQuery() {
    // Find the nodes that are translations of this node.
    $query = $this->select('node_revisions', 'nr');
    $query->join('node', 'n', 'n.nid = nr.nid');
    $query->join('node', 'nt', self::NODE_TRANSLATION_JOIN);

    // Find all translation revisions with lower vids than the current one.
    $query->join('node_revisions', 'nrt',
      'nrt.nid = nt.nid AND nr.vid >= nrt.vid');

    // For each vid/language pair, we only want the top translation vid.
    $query->groupBy('nr.vid');
    $query->groupBy('nt.language');
    $query->addField('nr', 'vid');
    $query->addField('nt', 'language');
    $query->addExpression('MAX(nrt.vid)', 'tvid');

    return $query;
  }

  /**
   * {@inheritdoc}
   */
  protected function translationQuery() {
    // Multiply each revision according to translationRevisionsQuery.
    $query = $this->select('node_revisions', 'nr');
    $query->join($this->translationRevisionsQuery(), 'tvids',
      'nr.vid = tvids.tvid');

    // Get fields according to this row of node_revisions.
    $query->addField('nr', 'vid', 'field_vid');
    // Claim to be from a translation, see 'vid' in the table from
    // translationRevisionsQuery.
    $query->fields('tvids', ['vid', 'language']);

    // Get the default node for this translation set.
    $query->join('node', 'nt', 'nt.nid = nr.nid');
    $query->join('node', 'n', self::NODE_TRANSLATION_JOIN);
    $query->where('n.tnid = 0 OR n.tnid = n.nid');
    $query->addField('n', 'nid');

    // Add orders, for reproducible results.
    $query->orderBy('vid');
    $query->orderBy('language');

    return $query;
  }

}
