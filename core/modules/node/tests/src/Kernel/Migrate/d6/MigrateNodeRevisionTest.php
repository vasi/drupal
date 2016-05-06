<?php

namespace Drupal\Tests\node\Kernel\Migrate\d6;

use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Row;
use Drupal\node\NodeInterface;

/**
 * Node content revisions migration.
 *
 * @group migrate_drupal_6
 */
class MigrateNodeRevisionTest extends MigrateNodeTestBase {
  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['language'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    // The revision migrations include translations, so we need to install
    // the necessary languages.
    ConfigurableLanguage::createFromLangcode('en')->save();
    ConfigurableLanguage::createFromLangcode('fr')->save();
  }

  /**
   * Test node revisions migration from Drupal 6 to 8.
   */
  public function testNodeRevision() {
    $this->executeMigrations(['d6_node', 'd6_node_revision']);
    $storage = \Drupal::entityManager()->getStorage('node');
    $node = $storage->loadRevision(2);

    /** @var \Drupal\node\NodeInterface $node */
    $this->assertIdentical('1', $node->id());
    $this->assertIdentical('2', $node->getRevisionId());
    $this->assertIdentical('und', $node->langcode->value);
    $this->assertIdentical('Test title rev 2', $node->getTitle());
    $this->assertIdentical('body test rev 2', $node->body->value);
    $this->assertIdentical('teaser test rev 2', $node->body->summary);
    $this->assertIdentical('2', $node->getRevisionUser()->id());
    $this->assertIdentical('modified rev 2', $node->revision_log->value);
    $this->assertIdentical('1390095702', $node->getRevisionCreationTime());

    $node = $storage->loadRevision(5);
    $this->assertIdentical('1', $node->id());
    $this->assertIdentical('body test rev 3', $node->body->value);
    $this->assertIdentical('1', $node->getRevisionUser()->id());
    $this->assertIdentical('modified rev 3', $node->revision_log->value);
    $this->assertIdentical('1390095703', $node->getRevisionCreationTime());

    // Revision 12 is the default revision of node 9.
    $node = $storage->loadRevision(12);
    $this->assertTrue($node instanceof NodeInterface);
    $this->assertIdentical('9', $node->id());
    // The French translation is only introduced in rev. 13.
    $this->assertTrue($node->hasTranslation('en'));
    $this->assertFalse($node->hasTranslation('fr'));
    $this->assertIdentical('The Real McCoy', $node->getTitle());
    $this->assertIdentical("In the original, Queen's English.", $node->body->value);

    // Revision 13 was part of node 10, which is a translation of node 9.
    $node = $storage->loadRevision(13);
    $this->assertTrue($node instanceof NodeInterface);
    $this->assertIdentical('9', $node->id());
    $this->assertTrue($node->isDefaultRevision());
    // English is the node's default language, in any revision.
    $this->assertIdentical('en', $node->language()->getId());
    // The English title and body did not change in this revision...
    $this->assertIdentical('The Real McCoy', $node->getTitle());
    $this->assertIdentical("In the original, Queen's English.", $node->body->value);
    // ...but a French translation was introduced.
    $this->assertTrue($node->hasTranslation('fr'));
    $node = $node->getTranslation('fr');
    $this->assertIdentical('Le Vrai McCoy', $node->getTitle());
    $this->assertIdentical("Ooh là là!", $node->body->value);

    // The node as a whole should have both languages.
    $node = $storage->load(9);
    $languages = array_keys($node->getTranslationLanguages());
    sort($languages);
    $this->assertIdentical(['en', 'fr'], $languages);

    // Check that the translations are actually present.
    $this->assertIdentical('The Real McCoy', $node->title->value);
    $this->assertIdentical('Le Vrai McCoy', $node->getTranslation('fr')->title->value);
  }

  /**
   * Test partial node revision migrations from Drupal 6 to 8.
   */
  public function testSomeNodeRevision() {
    $storage = \Drupal::entityManager()->getStorage('node');

    // Make sure we don't already have a revision.
    $this->assertNull($storage->loadRevision(13), 'No revision before migrations');

    $this->executeMigrations([
      'd6_node',
      'd6_node_revision'
    ], function (MigrationInterface $migration, Row $row) {
      // Only include nodes with nid 9.
      if ($row->getSourceProperty('nid') != 9) {
        return FALSE;
      }
      // Only include revision 13.
      if (strstr($migration->id(), 'node_revision') !== FALSE) {
        if ($row->getSourceProperty('vid') != 13) {
          return FALSE;
        }
      }
      return TRUE;
    });

    /** @var \Drupal\node\NodeInterface $node */
    $node = $storage->loadRevision(13);
    // Both languages should be present in this revision.
    $this->assertTrue($node->hasTranslation('en'), 'English translation exists');
    $this->assertIdentical('The Real McCoy', $node->getTranslation('en')
      ->getTitle());
    $this->assertTrue($node->hasTranslation('fr'), 'French translation exists');
    $this->assertIdentical('Le Vrai McCoy', $node->getTranslation('fr')
      ->getTitle());
  }

}
