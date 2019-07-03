<?php

namespace Drupal\bluecadet_issue_tracker\Controller;

use Drupal\Core\Render\Markup;
use Drupal\Core\Controller\ControllerBase;
use Github\Client as GithubClient;
use Drupal\bluecadet_issue_tracker\ZenHubAPI;

define("BCIT_CACHETIME", 3600);

/**
 *
 */
class ClientView extends ControllerBase {

  private $github_token = "";
  private $github_org = "";
  private $github_proj = "";
  private $github_label = "";

  private $client;
  private $zen;

  /**
   *
   */
  public function __construct() {
    $settings['bcit'] = \Drupal::state()->get('bluecadet_issue_tracker.settings', []);

    if (!empty($settings['bcit'])) {
      $this->github_token = $settings['bcit']['github']['github_token'];
      $this->github_org = $settings['bcit']['github']['github_org'];
      $this->github_proj = $settings['bcit']['github']['github_proj'];
      $this->github_label = $settings['bcit']['github']['github_label'];

      $this->client = new GithubClient();
      $this->client->authenticate($this->github_token, NULL, GithubClient::AUTH_HTTP_TOKEN);

      $this->zen = new ZenHubAPI();
    }
  }

  /**
   * Build the client view page.
   */
  public function build() {

    if (empty($this->github_token) || empty($this->github_org) || empty($this->github_proj) || empty($this->github_label)) {
      drupal_set_message("Configuration appears to not be set.", 'warning');
      return [];
    }

    $build = [
      '#prefix' => '<div id="issue-board">',
      '#suffix' => '</div>',
      '#attached' => [
        'library' => 'bluecadet_issue_tracker/issues',
      ],
    ];

    $issues = $this->getGithubIssues();

    $processed_issues = [];
    foreach ($issues as $issue) {
      $processed_issues[$issue['number']] = $issue;
    }

    // Get The ZenHub Boards.
    $boards = $this->getZenHubBoards();

    if (!empty($boards)) {
      foreach ($boards->pipelines as &$board) {
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

        $build['issues'][$board->id]['#title'] .= " (" . count($build['issues'][$board->id]['#items']) . ")";

      }
    }

    $closed_issues = $this->getGithubClosedIssues();

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

  protected function getGithubIssues() {

    $cid = "issues";
    if ($cache = \Drupal::cache('bcit_issue_tracker')->get($cid)) {
      // drupal_set_message("Issues Cache hit");
      $issues = $cache->data;
    }
    else {
      // drupal_set_message("Issues Cache miss");
      $issues = $this->client->api('issue')->all($this->github_org, $this->github_proj, ['labels' => $this->github_label]);

      \Drupal::cache('bcit_issue_tracker')->set($cid, $issues, (time() + BCIT_CACHETIME), ["bcit:github", "bcit:issues"]);
    }

    return $issues;
  }

  protected function getGithubClosedIssues() {

    $cid = "issues:closed";
    if ($cache = \Drupal::cache('bcit_issue_tracker')->get($cid)) {
      // drupal_set_message("CIssues Cache hit");
      $issues = $cache->data;
    }
    else {
      // drupal_set_message("CIssues Cache miss");
      $issues = $this->client->api('issue')->all($this->github_org, $this->github_proj, ['labels' => $this->github_label, 'state' => 'closed']);

      \Drupal::cache('bcit_issue_tracker')->set($cid, $issues, (time() + BCIT_CACHETIME), ["bcit:github", "bcit:issues", "bcit:issues:closed"]);
    }

    return $issues;
  }

  protected function getZenHubBoards() {
    $boards = [];

    $cid = "z_boards";
    if ($cache = \Drupal::cache('bcit_issue_tracker')->get($cid)) {
      // drupal_set_message("Boards Cache hit");
      $boards = $cache->data;
    }
    else {
      // drupal_set_message("Boards Cache miss");
      $boards = $this->zen->getAllBoards();

      \Drupal::cache('bcit_issue_tracker')->set($cid, $boards, (time() + BCIT_CACHETIME), ["bcit:zenhub", "bcit:zboards"]);
    }
    return $boards;
  }

}
