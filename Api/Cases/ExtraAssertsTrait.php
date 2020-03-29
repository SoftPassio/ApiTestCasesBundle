<?php

namespace SoftPassio\ApiTestCasesBundle\Api\Cases;

use Coduo\PHPMatcher\PHPUnit\PHPMatcherAssertions;
use Coduo\PHPMatcher\Factory\MatcherFactory;
use Symfony\Component\HttpFoundation\Response;
use Coduo\PHPMatcher\PHPMatcher;
use SebastianBergmann\Diff\Differ;
use SebastianBergmann\Diff\Output\UnifiedDiffOutputBuilder;

trait ExtraAssertsTrait
{
    use PHPMatcherAssertions;

    /**
     * @param Response $response
     * @param int $statusCode
     */
    protected function assertResponseCode(Response $response, $statusCode)
    {
        self::assertEquals($statusCode, $response->getStatusCode());
    }

    /**
     * Asserts that response has JSON content.
     * If filename is set, asserts that response content matches the one in given file.
     * If statusCode is set, asserts that response has given status code.
     *
     * @param string|null $filename
     * @param int|null $statusCode
     */
    protected function assertResponse(Response $response, $filename, $statusCode = 200)
    {
        $this->assertResponseCode($response, $statusCode);
        $this->assertJsonHeader($response, $statusCode);
        $this->assertJsonResponseContent($response, $filename);
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
    protected function assertResponseContent(string $actualResponse, string $filename, string $mimeType): void
    {
        $responseSource = $this->getExpectedResponsesFolder();

        $contents = file_get_contents(PathBuilder::build($responseSource, sprintf('%s.%s', $filename, $mimeType)));
        $contents = $this->prettifyJson($contents);

        $actualResponse = trim($actualResponse);
        $expectedResponse = trim($contents);

        $matcher = $this->buildMatcher();
        $result = $matcher->match(json_decode($actualResponse, true), json_decode($expectedResponse, true));

        if (!$result) {

            $differ = new Differ(
                new UnifiedDiffOutputBuilder(
                    "Json value is not matching expected format:". \PHP_EOL
                    .$matcher->getError(). \PHP_EOL,
                    true
                )
            );
            $diffOutput = $differ->diff(
                \json_encode(\json_decode($expectedResponse, true), JSON_PRETTY_PRINT),
                \json_encode(\json_decode($actualResponse, true), JSON_PRETTY_PRINT)
            );

            self::fail($diffOutput);
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
     * @param $content
     *
     * @return string
     */
    protected function prettifyJson($content): string
    {
        $jsonFlags = \JSON_PRETTY_PRINT;
        if (!isset($_SERVER['ESCAPE_JSON']) || true !== $_SERVER['ESCAPE_JSON']) {
            $jsonFlags |= \JSON_UNESCAPED_UNICODE | \JSON_UNESCAPED_SLASHES;
        }

        /** @var string $encodedContent */
        $encodedContent = json_encode(json_decode($content, true), $jsonFlags);

        return $encodedContent;
    }
}
