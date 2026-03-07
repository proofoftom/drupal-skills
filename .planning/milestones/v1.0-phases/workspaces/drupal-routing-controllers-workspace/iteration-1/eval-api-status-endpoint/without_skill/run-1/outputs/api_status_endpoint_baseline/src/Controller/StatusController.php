<?php

namespace Drupal\api_status_endpoint_baseline\Controller;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Returns a JSON response with site status information.
 */
class StatusController extends ControllerBase {

  protected ModuleHandlerInterface $moduleHandler;

  public function __construct(
    ModuleHandlerInterface $moduleHandler,
    ConfigFactoryInterface $configFactory,
  ) {
    $this->moduleHandler = $moduleHandler;
    $this->configFactory = $configFactory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('module_handler'),
      $container->get('config.factory'),
    );
  }

  /**
   * Returns site name and module count as JSON.
   */
  public function status(): JsonResponse {
    $site_name = $this->configFactory->get('system.site')->get('name');
    $module_count = count($this->moduleHandler->getModuleList());

    return new JsonResponse([
      'site_name' => $site_name,
      'module_count' => $module_count,
    ]);
  }

}
