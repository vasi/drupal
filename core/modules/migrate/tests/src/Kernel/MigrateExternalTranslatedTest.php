<?php

namespace Drupal\Tests\migrate\Kernel;

use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\migrate\MigrateExecutable;
use Drupal\node\Entity\NodeType;

/**
 * Tests migrating non-Drupal translated content.
 *
 * Ensure it's possible to migrate in translations, even if there's no nid or
 * tnid property on the source.
 *
 * @group migrate
 */
class MigrateExternalTranslatedTest extends MigrateTestBase {

  /**
   * {@inheritdoc}
   *
   * @todo: Remove migrate_drupal when https://www.drupal.org/node/2560795 is
   * fixed.
   */
  public static $modules = ['system', 'user', 'language', 'node', 'field', 'migrate_drupal', 'migrate_external_translated_test'];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->installSchema('system', ['sequences']);
    $this->installSchema('node', array('node_access'));
    $this->installEntitySchema('user');
    $this->installEntitySchema('node');

    // Create some languages.
    ConfigurableLanguage::createFromLangcode('en')->save();
    ConfigurableLanguage::createFromLangcode('fr')->save();
    ConfigurableLanguage::createFromLangcode('es')->save();

    // Create a content type.
    NodeType::create([
      'type' => 'external_test',
      'name' => 'Test node type',
    ])->save();
  }

  /**
   * Test importing and rolling back our data.
   */
  public function testMigrations() {
    /** @var \Drupal\Core\Entity\ContentEntityStorageInterface $storage */
    $storage = $this->container->get('entity.manager')->getStorage('node');
    $this->assertEquals(0, count($storage->loadMultiple()));

    // Run the migrations.
    $migration_ids = ['external_translated_test_node', 'external_translated_test_node_translation'];
    $this->executeMigrations($migration_ids);
    $this->assertEquals(3, count($storage->loadMultiple()));

    $node = $storage->load(1);
    $this->assertEquals('en', $node->language()->getId());
    $this->assertEquals('Cat', $node->title->value);
    $this->assertEquals('Chat', $node->getTranslation('fr')->title->value);
    $this->assertEquals('Gato', $node->getTranslation('es')->title->value);

    $node = $storage->load(2);
    $this->assertEquals('en', $node->language()->getId());
    $this->assertEquals('Dog', $node->title->value);
    $this->assertEquals('Chien', $node->getTranslation('fr')->title->value);
    $this->assertFalse($node->hasTranslation('es'));

    $node = $storage->load(3);
    $this->assertEquals('en', $node->language()->getId());
    $this->assertEquals('Monkey', $node->title->value);
    $this->assertFalse($node->hasTranslation('fr'));
    $this->assertFalse($node->hasTranslation('es'));

    $this->assertNull($storage->load(4));

    // Roll back the migrations.
    foreach ($migration_ids as $migration_id) {
      $migration = $this->getMigration($migration_id);
      $executable = new MigrateExecutable($migration, $this);
      $executable->rollback();
    }

    $this->assertEquals(0, count($storage->loadMultiple()));
  }

}
