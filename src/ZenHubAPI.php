<?php

namespace Drupal\bluecadet_issue_tracker;

/**
 *
 */
class ZenHubAPI {
  private $zenhub_root = "https://api.zenhub.io/p1";
  private $repo_id = "";
  private $zenhub_api_token = "";

  /**
   *
   */
  public function __construct() {
    $settings['bcit'] = \Drupal::state()->get('bluecadet_issue_tracker.settings', []);

    $this->zenhub_root = $settings['bcit']['zenhub']['base_url'];
    $this->repo_id = $settings['bcit']['zenhub']['zenhub_repo_id'];
    $this->zenhub_api_token = $settings['bcit']['zenhub']['zenhub_api_token'];
  }

  /**
   *
   */
  public function getAllBoards() {
    $path = "/repositories/" . $this->repo_id . "/board";

    $data = $this->GetCall($path);

    return $data;
  }

  /**
   *
   */
  private function GetCall($path, $params = []) {
    $ch = curl_init();

    $call_url = $this->zenhub_root . $path;
    if (!empty($params)) {
      $call_url .= '?' . http_build_query($params);
    }

    curl_setopt($ch, CURLOPT_URL, $call_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);

    curl_setopt($ch, CURLOPT_HTTPHEADER, [
      'X-Authentication-Token: ' . $this->zenhub_api_token,
    ]);

    $return = curl_exec($ch);
    // ksm($return);
    $data = json_decode($return);
    // ksm($data);
    curl_close($ch);

    return $data;
  }

}
