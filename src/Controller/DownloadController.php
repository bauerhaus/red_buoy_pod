<?php

namespace Drupal\redbuoy_media_pod\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Connection;
use Drupal\Core\File\FileSystemInterface;
use Drupal\file\FileInterface;
use Drupal\node\NodeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Controller for podcast episode downloads with tracking.
 */
class DownloadController extends ControllerBase {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected Connection $database;

  /**
   * The file system service.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected FileSystemInterface $fileSystem;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected RequestStack $requestStack;

  /**
   * DownloadController constructor.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   * @param \Drupal\Core\File\FileSystemInterface $file_system
   *   The file system service.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   */
  public function __construct(
    Connection $database,
    FileSystemInterface $file_system,
    RequestStack $request_stack
  ) {
    $this->database = $database;
    $this->fileSystem = $file_system;
    $this->requestStack = $request_stack;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('database'),
      $container->get('file_system'),
      $container->get('request_stack')
    );
  }

  /**
   * Serves the podcast file and logs the download.
   *
   * @param int $node_id
   *   The node ID of the podcast episode.
   * @param int $file_id
   *   The file ID of the MP3 file.
   *
   * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
   *   The file download response.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
   *   If the node or file doesn't exist.
   */
  public function download(int $node_id, int $file_id): BinaryFileResponse {
    // Load the node.
    $node = $this->entityTypeManager()->getStorage('node')->load($node_id);

    if (!$node instanceof NodeInterface || $node->bundle() !== 'podcast_episode') {
      throw new NotFoundHttpException('Podcast episode not found.');
    }

    // Load the file.
    $file = $this->entityTypeManager()->getStorage('file')->load($file_id);

    if (!$file instanceof FileInterface) {
      throw new NotFoundHttpException('Audio file not found.');
    }

    // Verify the file exists on disk.
    $uri = $file->getFileUri();
    $real_path = $this->fileSystem->realpath($uri);

    if (!file_exists($real_path)) {
      throw new NotFoundHttpException('Audio file does not exist on disk.');
    }

    // Get request details for logging.
// Get request details for logging.
    $request = $this->requestStack->getCurrentRequest();
    $ip_address = $request->getClientIp() ?? '';
    $user_agent = $request->headers->get('User-Agent') ?? '';
    $referer = $request->headers->get('Referer') ?? '';
    $feed_name = $node->get('field_podcast_feed')->value ?? 'unknown';

    // Only log if this is a new download (not a range request resumption).
    // Check if we've already logged this IP + file combo in the last hour.
    $one_hour_ago = time() - 3600;
    $existing = $this->database->select('redbuoy_podcast_downloads', 'd')
      ->fields('d', ['id'])
      ->condition('file_id', $file_id)
      ->condition('ip_address', substr($ip_address, 0, 45))
      ->condition('timestamp', $one_hour_ago, '>')
      ->range(0, 1)
      ->execute()
      ->fetchField();

    // Log the download only if no recent entry exists.
    if (!$existing) {
      $this->database->insert('redbuoy_podcast_downloads')
        ->fields([
          'timestamp' => time(),
          'node_id' => $node_id,
          'file_id' => $file_id,
          'feed_name' => $feed_name,
          'ip_address' => substr($ip_address, 0, 45),
          'user_agent' => substr($user_agent, 0, 512),
          'referer' => substr($referer, 0, 512),
        ])
        ->execute();
    }

    // Serve the file.
    $response = new BinaryFileResponse($real_path);
    $response->setContentDisposition('inline', $file->getFilename());
    $response->headers->set('Content-Type', 'audio/mpeg');
    $response->headers->set('Content-Length', filesize($real_path));

    return $response;
  }

}
