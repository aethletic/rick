<?php

namespace Botify4\Http;

use Botify4\Bot;

class Request
{
  private $api_url;
  private $curl;

  public function __construct($api_url)
  {
    $this->api_url = $api_url;
    $this->curl = curl_init();
  }

  public function call($method, $parameters = [], $is_file = false) {

    if ($is_file) {
        $headers = 'Content-Type: multipart/form-data';
    } else {
        $headers = 'Content-Type: application/json';
        $parameters = json_encode($parameters);
    }

    $url= $this->api_url . '/' . $method;

    curl_setopt($this->curl, CURLOPT_URL, $url);
    curl_setopt($this->curl, CURLINFO_HEADER_OUT, true);
    curl_setopt($this->curl, CURLOPT_HTTPHEADER, [$headers]);
    curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($this->curl, CURLOPT_POST, true);
    curl_setopt($this->curl, CURLOPT_POSTFIELDS, $parameters);

    $response = curl_exec($this->curl);

    return json_decode($response, true);
  }
}
