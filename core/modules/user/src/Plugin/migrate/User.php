<?php

namespace Drupal\user\Plugin\migrate;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\migrate\Exception\RequirementsException;
use Drupal\migrate\Plugin\MigrateDestinationPluginManager;
use Drupal\migrate\Plugin\MigratePluginManager;
use Drupal\migrate\Plugin\Migration;
use Drupal\migrate\Plugin\MigrationPluginManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin class for Drupal 7 user migrations dealing with fields and profiles.
 */
class User extends Migration {
  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * Constructs a user migration.
   *
   * @param array $configuration
   *   Plugin configuration.
   * @param string $plugin_id
   *   The plugin ID.
   * @param mixed $plugin_definition
   *   The plugin definition.
   * @param \Drupal\migrate\Plugin\MigrationPluginManagerInterface $migration_plugin_manager
   *   The migration plugin manager.
   * @param \Drupal\migrate\Plugin\MigratePluginManager $source_plugin_manager
   *   The source migration plugin manager.
   * @param \Drupal\migrate\Plugin\MigratePluginManager $process_plugin_manager
   *   The process migration plugin manager.
   * @param \Drupal\migrate\Plugin\MigrateDestinationPluginManager $destination_plugin_manager
   *   The destination migration plugin manager.
   * @param \Drupal\migrate\Plugin\MigratePluginManager $idmap_plugin_manager
   *   The ID map migration plugin manager.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, MigrationPluginManagerInterface $migration_plugin_manager, MigratePluginManager $source_plugin_manager, MigratePluginManager $process_plugin_manager, MigrateDestinationPluginManager $destination_plugin_manager, MigratePluginManager $idmap_plugin_manager, ModuleHandlerInterface $module_handler, LanguageManagerInterface $language_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $migration_plugin_manager, $source_plugin_manager, $process_plugin_manager, $destination_plugin_manager, $idmap_plugin_manager);
    $this->moduleHandler = $module_handler;
    $this->languageManager = $language_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('plugin.manager.migration'),
      $container->get('plugin.manager.migrate.source'),
      $container->get('plugin.manager.migrate.process'),
      $container->get('plugin.manager.migrate.destination'),
      $container->get('plugin.manager.migrate.id_map'),
      $container->get('module_handler'),
      $container->get('language_manager')
    );
  }

  /**
   * Flag indicating whether the CCK data has been filled already.
   *
   * @var bool
   */
  protected $init = FALSE;

  /**
   * {@inheritdoc}
   */
  public function getProcess() {
    if (!$this->init) {
      $this->init = TRUE;
      $definition['source'] = [
        'entity_type' => 'user',
        'ignore_map' => TRUE,
      ] + $this->source;
      $definition['destination']['plugin'] = 'null';
      if ($this->moduleHandler->moduleExists('field')) {
        $definition['source']['plugin'] = 'd7_field_instance';
        $field_migration = $this->migrationPluginManager->createStubMigration($definition);
        foreach ($field_migration->getSourcePlugin() as $row) {
          $field_name = $row->getSourceProperty('field_name');
          $this->process[$field_name] = $field_name;
        }
      }
      try {
        $definition['source']['plugin'] = 'profile_field';
        $profile_migration = $this->migrationPluginManager->createStubMigration($definition);
        // Ensure that Profile is enabled in the source DB.
        $profile_migration->checkRequirements();
        foreach ($profile_migration->getSourcePlugin() as $row) {
          $name = $row->getSourceProperty('name');
          $this->process[$name] = $name;
        }
      }
      catch (RequirementsException $e) {
        // The checkRequirements() call will fail when the profile module does
        // not exist on the source site.
      }

      // Use the default language if none is provided.
      $this->process['langcode'] = [
        'source' => 'language',
        'plugin' => 'default_value',
        'strict' => FALSE,
        'default_value' => $this->languageManager->getDefaultLanguage()->getId(),
      ];

    }
    return parent::getProcess();
  }

}
