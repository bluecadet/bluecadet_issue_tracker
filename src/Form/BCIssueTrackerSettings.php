<?php

namespace Drupal\bluecadet_issue_tracker\Form;

use Drupal\Core\Link;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 *
 */
class BCIssueTrackerSettings extends FormBase {

  /**
   *
   */
  public function getFormId() {
    return 'bluecadet_issue_tracker_settings';
  }

  /**
   *
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $settings['bcit'] = \Drupal::state()->get('bluecadet_issue_tracker.settings', []);
    $form['#tree'] = TRUE;

    $link_options = [
      'absolute' => TRUE,
      'attributes' => [
        'class' => 'this-class',
      ],
    ];

    $form['link'] = [
      Link::createFromRoute("View Issues", 'bluecadet_issue_tracker.client', [], $link_options)->toRenderable(),
    ];

    $form['bcit']['github'] = [
      '#type' => 'fieldset',
      '#title' => 'GitHub Settings',
    ];
    $form['bcit']['github']['github_token'] = [
      '#type' => 'textfield',
      '#title' => t('Github API Token'),
      '#default_value' => isset($settings['bcit']['github']['github_token']) ? $settings['bcit']['github']['github_token'] : '',
    ];
    $form['bcit']['github']['github_org'] = [
      '#type' => 'textfield',
      '#title' => t('Github Org Id'),
      '#default_value' => isset($settings['bcit']['github']['github_org']) ? $settings['bcit']['github']['github_org'] : 'bluecadet',
    ];
    $form['bcit']['github']['github_proj'] = [
      '#type' => 'textfield',
      '#title' => t('Github Project ID'),
      '#default_value' => isset($settings['bcit']['github']['github_proj']) ? $settings['bcit']['github']['github_proj'] : '',
    ];
    $form['bcit']['github']['github_label'] = [
      '#type' => 'textfield',
      '#title' => t('Github Client Label'),
      '#description' => $this->t("This will be the Label we filter on and show to the client."),
      '#default_value' => isset($settings['bcit']['github']['github_label']) ? $settings['bcit']['github']['github_label'] : '',
    ];

    $form['bcit']['zenhub'] = [
      '#type' => 'fieldset',
      '#title' => 'ZenHub Settings',
    ];
    $form['bcit']['zenhub']['base_url'] = [
      '#type' => 'textfield',
      '#title' => t('Zenhub Base Url'),
      '#description' => "Do not inclue the trailing slash",
      '#default_value' => isset($settings['bcit']['zenhub']['base_url']) ? $settings['bcit']['zenhub']['base_url'] : 'https://api.zenhub.io/p1',
    ];
    $form['bcit']['zenhub']['zenhub_repo_id'] = [
      '#type' => 'textfield',
      '#title' => t('Github Org Id'),
      '#default_value' => isset($settings['bcit']['zenhub']['zenhub_repo_id']) ? $settings['bcit']['zenhub']['zenhub_repo_id'] : '',
    ];
    $form['bcit']['zenhub']['zenhub_api_token'] = [
      '#type' => 'textfield',
      '#title' => t('Github Project ID'),
      '#default_value' => isset($settings['bcit']['zenhub']['zenhub_api_token']) ? $settings['bcit']['zenhub']['zenhub_api_token'] : '',
    ];

    // Actions.
    $form['submit'] = [
      '#type' => 'submit',
      '#name' => 'submit',
      '#value' => $this->t('Save Settings'),
    ];

    return $form;
  }

  /**
   *
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();

    $to_save = $values['bcit'];

    \Drupal::state()->set('bluecadet_issue_tracker.settings', $to_save);
  }

}
