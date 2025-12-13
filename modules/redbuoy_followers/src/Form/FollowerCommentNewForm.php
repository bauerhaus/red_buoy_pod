<?php

declare(strict_types=1);

namespace Drupal\redbuoy_followers\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Custom “Leave a comment” form for podcast followers.
 */
final class FollowerCommentNewForm extends FormBase {

  private ?NodeInterface $episode = NULL;
  private string $feed = '';

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'redbuoy_followers_new_comment_form';
  }

  /**
   * Build form.
   *
   * @param \Drupal\node\NodeInterface $episode
   *   The podcast episode node.
   * @param string $feed
   *   The feed id (must match episode).
   */
  public function buildForm(array $form, FormStateInterface $form_state, NodeInterface $episode = NULL, string $feed = ''): array {
    if (!$episode) {
      throw new NotFoundHttpException();
    }
    $this->episode = $episode;
    $this->feed = $feed;

    $form['intro'] = [
      '#markup' => $this->t('<p>Leave a short note for Harmonia. Your comment will appear after moderation.</p>'),
    ];

    // Hidden context (we do NOT let users edit these).
    $form['node'] = [
      '#type' => 'hidden',
      '#value' => (string) $episode->id(),
    ];
    $form['feed'] = [
      '#type' => 'hidden',
      '#value' => $feed,
    ];

    $form['field_rb_follower_first_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('First name or handle'),
      '#required' => TRUE,
      '#maxlength' => 255,
    ];

    $form['field_rb_follower_comment'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Comment'),
      '#required' => TRUE,
      '#rows' => 8,
      '#description' => $this->t('Plain text only.'),
    ];

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit comment'),
      '#button_type' => 'primary',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {
    $nid = (int) $form_state->getValue('node');
    $feed = (string) $form_state->getValue('feed');

    $episode = Node::load($nid);
    if (!$episode || $episode->bundle() !== 'podcast_episode' || !$episode->isPublished()) {
      $form_state->setErrorByName('field_rb_follower_comment', $this->t('Invalid episode.'));
      return;
    }
    if (!$episode->hasField('field_podcast_feed') || $episode->get('field_podcast_feed')->isEmpty()) {
      $form_state->setErrorByName('field_rb_follower_comment', $this->t('Invalid episode feed.'));
      return;
    }
    $episode_feed = (string) $episode->get('field_podcast_feed')->value;

    if ($feed !== $episode_feed) {
      $form_state->setErrorByName('field_rb_follower_comment', $this->t('Invalid request.'));
      return;
    }

    // Basic length sanity.
    $comment = trim((string) $form_state->getValue('field_rb_follower_comment'));
    if (mb_strlen($comment) < 3) {
      $form_state->setErrorByName('field_rb_follower_comment', $this->t('Comment is too short.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $nid = (int) $form_state->getValue('node');
    $feed = (string) $form_state->getValue('feed');

    /** @var \Drupal\node\NodeInterface|null $episode */
    $episode = Node::load($nid);
    if (!$episode) {
      throw new NotFoundHttpException();
    }

    $first = trim((string) $form_state->getValue('field_rb_follower_first_name'));
    $comment = trim((string) $form_state->getValue('field_rb_follower_comment'));

    // Create follower_comment node (UNPUBLISHED by default).
    $node = Node::create([
      'type' => 'follower_comment',
      'status' => 0,
      'title' => $this->t('Follower comment on @title', ['@title' => $episode->label()]),
      'field_rb_follower_first_name' => $first,
      'field_rb_follower_episode' => ['target_id' => $episode->id()],
      'field_podcast_feed' => $feed,
      'field_rb_follower_comment' => [
        'value' => $comment,
        'format' => 'plain_text',
      ],
    ]);

    $node->save();

    $this->messenger()->addStatus($this->t('Thank you. Your comment was received and will appear after moderation.'));
    $form_state->setRedirectUrl($episode->toUrl());
  }
}
