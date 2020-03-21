<?php

namespace AppVerk\ApiTestCasesBundle\Api\Cases;

use Symfony\Component\HttpFoundation\Response;

trait ExtraAssertsTrait
{
    /**
     * Asserts that response has JSON content.
     * If filename is set, asserts that response content matches the one in given file.
     * If statusCode is set, asserts that response has given status code.
     *
     * @param string|null $filename
     * @param int|null    $statusCode
     */
    protected function assertResponse(Response $response, $filename, $statusCode = 200)
    {
        $this->assertResponseCode($response, $statusCode);
        $this->assertJsonHeader($response, $statusCode);
        $this->assertJsonResponseContent($response, $filename);
    }

    /**
     * @param int $statusCode
     */
    protected function assertResponseCode(Response $response, $statusCode)
    {
        self::assertEquals($statusCode, $response->getStatusCode());
    }

    protected function assertJsonHeader(Response $response, $statusCode)
    {
        $contentType = 'application/json';
        if ($statusCode >= 400) {
            $contentType = 'application/problem+json';
        }
        self::assertHeader($response, $contentType);
    }

    /**
     * Asserts that response has JSON content matching the one given in file.
     *
     * @param string $filename
     *
     * @throws \Exception
     */
    protected function assertJsonResponseContent(Response $response, $filename)
    {
        $this->assertResponseContent($this->prettifyJson($response->getContent()), $filename, 'json');
    }

    /**
     * @param string $actualResponse
     * @param string $filename
     * @param string $mimeType
     */
    protected function assertResponseContent($actualResponse, $filename, $mimeType)
    {
        $responseSource = $this->getExpectedResponsesFolder();
        $actualResponse = trim($actualResponse);
        $expectedResponse = trim(
            file_get_contents(PathBuilder::build($responseSource, sprintf('%s.%s', $filename, $mimeType)))
        );
        $matcher = $this->buildMatcher();
        $result = $matcher->match($actualResponse, $expectedResponse);
        if (!$result) {
            $diff = new \Diff(explode(PHP_EOL, $expectedResponse), explode(PHP_EOL, $actualResponse), []);
            self::fail($matcher->getError().PHP_EOL.$diff->render(new \Diff_Renderer_Text_Unified()));
        }
    }

    /**
     * @param string $contentType
     */
    protected function assertHeader(Response $response, $contentType)
    {
        self::assertTrue(
            ($response->headers->get('Content-Type') == $contentType),
            $response->headers->get('Content-Type')
        );
    }

    /**
     * @return string
     */
    protected function getExpectedResponsesFolder()
    {
        if (null === $this->expectedResponsesPath) {
            $this->expectedResponsesPath = isset($_SERVER['EXPECTED_RESPONSE_DIR']) ?
                PathBuilder::build($this->getRootDir(), $_SERVER['EXPECTED_RESPONSE_DIR']) :
                PathBuilder::build($this->getCalledClassFolder(), '..', 'Responses', 'Expected');
        }

        return $this->expectedResponsesPath;
    }

    /**
     * {@inheritdoc}
     */
    protected function buildMatcher()
    {
        return MatcherFactory::buildJsonMatcher();
    }

    /**
     * @param $content
     *
     * @return string
     */
    protected function prettifyJson($content)
    {
        return json_encode(json_decode($content), JSON_PRETTY_PRINT);
    }
}
