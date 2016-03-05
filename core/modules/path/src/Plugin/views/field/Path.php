<?php

/**
 * @file
 * Contains \Drupal\path\Plugin\views\field\Path.
 */

namespace Drupal\path\Plugin\views\field;

use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\views\Entity\Render\EntityTranslationRenderTrait;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;
use Drupal\views\ViewExecutable;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Field handler to present the path to any entity.
 *
 * @ViewsField("entity_path")
 */
class Path extends FieldPluginBase {
  use EntityTranslationRenderTrait;

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  public $entityManager;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * {@inheritdoc}
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityManagerInterface $entity_manager, LanguageManagerInterface $language_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->entityManager = $entity_manager;
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
      $container->get('entity.manager'),
      $container->get('language_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function init(ViewExecutable $view, DisplayPluginBase $display, array &$options = NULL) {
    parent::init($view, $display, $options);
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityTypeId() {
    $entity_type = $this->getEntityType();
    $entity_definition = $this->entityManager->getDefinition($entity_type);
    return $entity_definition->id();
  }

  /**
   * {@inheritdoc}
   */
  protected function getEntityManager() {
    return $this->entityManager;
  }

  /**
   * {@inheritdoc}
   */
  protected function getLanguageManager() {
    return $this->languageManager;
  }

  /**
   * {@inheritdoc}
   */
  protected function getView() {
    return $this->view;
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    $this->getEntityTranslationRenderer()->query($this->view->getQuery());
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    $langcode = $this->getEntityTranslationRenderer()->getLangcode($values);
    $language = $this->languageManager->getLanguage($langcode);
    $entity = $this->getEntity($values);
    $url = $entity->toUrl('canonical', ['language' => $language]);
    return $url->toString();
  }

}
