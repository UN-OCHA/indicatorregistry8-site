<?php

namespace Drupal\tome_static_azure\Form;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Batch\BatchBuilder;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Site\Settings;
use Drupal\Core\State\StateInterface;
use Drupal\tome_static_azure\StaticAzureSyncInterface;
use Drupal\tome_static\TomeStaticHelper;
use Drupal\tome_static\StaticUITrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Contains a form for initializing a static build.
 *
 * @internal
 */
class StaticAzureSyncForm extends FormBase {

  use StaticUITrait;

  /**
   * The state system.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * StaticAzureSyncForm constructor.
   *
   * @param \Drupal\Core\State\StateInterface $state
   *   The state system.
   */
  public function __construct(StateInterface $state) {
    $this->state = $state;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('state'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'tome_static_azure_sync_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $azure_config   = \Drupal::configFactory()->get('azure_storage.settings');
    $tome_directory = Settings::get('tome_static_directory', '../html');

    $form['description'] = [
      '#markup' => '<p>' . $this->t('Submitting this form will upload the contents of the static export directory (@dir) to the static website container in the Azure storage account at <em>@account/$web</em>.', [
        '@dir' => $tome_directory,
        '@account' => $azure_config->get('account_name'),
      ]) . '</p>',
    ];

    $warnings = $this->getWarnings();
    if ($this->state->get(StaticAzureSyncInterface::STATE_KEY_AZURE_SYNC, FALSE)) {
      $warnings[] = $this->t('Another user may be running a synchronisation, proceed only if the last upload failed unexpectedly.');
    }

    if (!empty($warnings)) {
      $form['warnings'] = [
        '#type' => 'container',
        'title' => [
          '#markup' => '<strong>' . $this->t('Synchronisation warnings') . '</strong>',
        ],
        'list' => [
          '#theme' => 'item_list',
          '#items' => [],
        ],
      ];
      foreach ($warnings as $warning) {
        $form['warnings']['list']['#items'][] = [
          '#markup' => $warning,
        ];
      }
    }

    $form['actions'] = [
      '#type' => 'actions',
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->state->set(StaticAzureSyncInterface::STATE_KEY_AZURE_SYNC, TRUE);

    $this->setBatch();
  }

  /**
   * Exports all remaining paths at the end of a previous batch.
   *
   * @param string $base_url
   *   The base URL.
   * @param array $context
   *   The batch context.
   */
  public function batchInvokePaths($base_url, array &$context) {
    if (!empty($context['results']['invoke_paths'])) {
      $context['results']['old_paths'] = isset($context['results']['old_paths']) ? $context['results']['old_paths'] : [];
      $context['results']['invoke_paths'] = array_diff($context['results']['invoke_paths'], $context['results']['old_paths']);
      $context['results']['old_paths'] = array_merge($context['results']['invoke_paths'], $context['results']['old_paths']);
      $invoke_paths = $this->static->exportPaths($context['results']['invoke_paths']);
      if (!empty($invoke_paths)) {
        $this->setBatch($invoke_paths, $base_url);
      }
    }
  }

  /**
   * Exports a path using Tome.
   *
   * @param string $path
   *   The path to export.
   * @param string $base_url
   *   The base URL.
   * @param array $context
   *   The batch context.
   */
  public function exportPath($path, $base_url, array &$context) {
    $original_params = TomeStaticHelper::setBaseUrl($this->getRequest(), $base_url);

    $this->requestPreparer->prepareForRequest();
    try {
      $invoke_paths = $this->static->requestPath($path);
    }
    catch (\Exception $e) {
      $context['results']['errors'][] = $this->formatPathException($path, $e);
      $invoke_paths = [];
    }

    TomeStaticHelper::restoreBaseUrl($this->getRequest(), $original_params);

    $context['results']['invoke_paths'] = isset($context['results']['invoke_paths']) ? $context['results']['invoke_paths'] : [];
    $context['results']['invoke_paths'] = array_merge($context['results']['invoke_paths'], $invoke_paths);
  }

  /**
   * Batch finished callback after all paths and assets have been exported.
   *
   * @param bool $success
   *   Whether or not the batch was successful.
   * @param mixed $results
   *   Batch results set with context.
   */
  public function finishCallback($success, $results) {
    $this->state->set(StaticAzureSyncInterface::STATE_KEY_AZURE_SYNC, FALSE);

    $this->messenger()->deleteAll();
    if (!$success) {
      $this->messenger()->addError($this->t('Azure sync failed - consult the error log for more details.'));
      return;
    }
    if (!empty($results['errors'])) {
      foreach ($results['errors'] as $error) {
        $this->messenger()->addError($error);
      }
    }
    $this->messenger()->addStatus($this->t('Azure sync complete! You should now be able to access your static website via the configured endpoint.'));
  }

  /**
   * Sets a new batch.
   *
   * @param array $paths
   *   An array of paths to invoke.
   * @param string $base_url
   *   The base URL.
   */
  protected function setBatch(array $paths, $base_url) {
    $batch_builder = (new BatchBuilder())
      ->setTitle($this->t('Uploading static HTML...'))
      ->setFinishCallback([$this, 'finishCallback']);
    $paths = $this->static->exportPaths($paths);
    foreach ($paths as $path) {
      $batch_builder->addOperation([$this, 'exportPath'], [$path, $base_url]);
    }
    $batch_builder->addOperation([$this, 'batchInvokePaths'], [$base_url]);
    batch_set($batch_builder->toArray());
  }

}
