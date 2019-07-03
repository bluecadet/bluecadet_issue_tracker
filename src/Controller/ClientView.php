<?php

namespace Drupal\bluecadet_issue_tracker\Controller;

use Drupal\Core\Render\Markup;
use Drupal\Core\Controller\ControllerBase;
use Github\Client as GithubClient;
use Drupal\bluecadet_issue_tracker\ZenHubAPI;

class ClientView extends ControllerBase {

  private $github_token = "";
  private $github_org = "";
  private $github_proj = "";
  private $github_label = "";

  function __construct() {
    $settings['bcit'] = \Drupal::state()->get('bluecadet_issue_tracker.settings', []);

    if (!empty($settings['bcit'])) {
      $this->github_token = $settings['bcit']['github']['github_token'];
      $this->github_org = $settings['bcit']['github']['github_org'];
      $this->github_proj = $settings['bcit']['github']['github_proj'];
      $this->github_label = $settings['bcit']['github']['github_label'];
    }
  }

  public function build() {

    if (empty($this->github_token) || empty($this->github_org) || empty($this->github_proj) || empty($this->github_label) ) {
      drupal_set_message("Configuration appears to not be set.", 'warning');
      return [];
    }

    $build = [
      '#prefix' => '<div id="issue-board">',
      '#suffix' => '</div>',
      '#attached' => [
        'library' => 'bluecadet_issue_tracker/issues',
      ]
    ];

    $client = new GithubClient();
    $client->authenticate($this->github_token, NULL, GithubClient::AUTH_HTTP_TOKEN);

    $issues = $client->api('issue')->all($this->github_org, $this->github_proj, ['labels' => $this->github_label]);
    $processed_issues = [];
    foreach ($issues as $issue) {
      $processed_issues[$issue['number']] = $issue;
    }

    $zen = new ZenHubAPI();
    $boards = $zen->getAllBoards();
    // ksm($boards);

    if (!empty($boards)) {
      foreach($boards->pipelines as &$board) {
        $boards->to_show = [];

        $build['issues'][$board->id] = [
          '#theme' => 'item_list',
          '#items' => [],
          '#title' => $board->name,
          '#empty' => "There are no issues to show",
        ];

        foreach ($board->issues as $b_issue) {
          if (isset($processed_issues[$b_issue->issue_number])) {
            $i = $processed_issues[$b_issue->issue_number];
            $boards->to_show[] = $i;

            $build['issues'][$board->id]['#items'][] = ['#markup' => Markup::create("[#" . $i['number'] . "] " . $i['title'])];
          }
        }
      }
    }

    $closed_issues = $client->api('issue')->all($this->github_org, $this->github_proj, ['labels' => $this->github_label, 'state' => 'closed']);

    $build['issues']["closed"] = [
      '#theme' => 'item_list',
      '#items' => [],
      '#title' => "Closed",
      '#empty' => "There are no issues to show",
    ];

    foreach ($closed_issues as &$i) {
      $build['issues']["closed"]['#items'][] = ['#markup' => Markup::create("[#" . $i['number'] . "] " . $i['title'])];
    }

    return $build;
  }
}