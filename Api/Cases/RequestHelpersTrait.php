<?php

namespace SoftPassio\ApiTestCasesBundle\Api\Cases;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

trait RequestHelpersTrait
{
    protected function makeRequest(
        string $method = 'GET',
        string $url = '/',
        array $data = [],
        array $headers = ['CONTENT_TYPE' => 'application/json']
    ): Response {
        $allHeaders = array_merge($headers, ['CONTENT_TYPE' => 'application/json']);
        $this->client->request($method, $url, [], [], $allHeaders, json_encode($data));

        return $this->client->getResponse();
    }

    protected function postRequest(
        string $url = '/',
        array $data = [],
        array $headers = ['CONTENT_TYPE' => 'application/json']
    ): Response {
        return $this->makeRequest(Request::METHOD_POST, $url, $data, $headers);
    }

    protected function putRequest(
        string $url = '/',
        array $data = [],
        array $headers = ['CONTENT_TYPE' => 'application/json']
    ): Response {
        return $this->makeRequest(Request::METHOD_PUT, $url, $data, $headers);
    }

    protected function deleteRequest(
        string $url = '/',
        array $headers = ['CONTENT_TYPE' => 'application/json']
    ): Response {
        return $this->makeRequest(Request::METHOD_DELETE, $url, [], $headers);
    }
}
