<?php

namespace SoftPassio\ApiTestCasesBundle\Api\Cases;

use Fidry\AliceDataFixtures\Loader\PurgerLoader;
use Nelmio\Alice\Loader\NativeLoader;
use Symfony\Bundle\FrameworkBundle\Client;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Finder\Finder;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Coduo\PHPMatcher\Matcher;
use Coduo\PHPMatcher\Factory\MatcherFactory;

class ApiTestCase extends WebTestCase
{
    use ExtraAssertsTrait;
    use RequestHelpersTrait;

    /**
     * @var string
     */
    protected $dataFixturesPath;

    /**
     * @var string
     */
    protected $expectedResponsesPath;

    /**
     * @var array
     */
    protected $fixutreFiles = [];

    /**
     * @var Client
     */
    protected $client;

    /** @var PurgerLoader */
    private $loader;

    public function __construct($name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);

        $this->matcherFactory = new MatcherFactory();
    }

    protected function buildMatcher(): Matcher
    {
        return $this->matcherFactory->createMatcher();
    }

    protected function setUp(): void
    {
        $this->expectedResponsesPath = $this->getExpectedResponsesFolder();
        $this->client = static::createClient();
    }

    public function tearDown(): void
    {
        $purger = new ORMPurger($this->getDoctrine()->getManager());
        $purger->purge();
    }

    /**
     * @return Fixtures
     */
    protected function getFixtureLoader($managerName = null)
    {
        $managerName = ($managerName) ? $managerName : 'fidry_alice_data_fixtures.loader.doctrine';
        return $this->getService($managerName);
    }

    protected function addFixtureFiles($source, $managerName = null)
    {
        if(!is_array($source)){
            $source = [$source];
        }
        foreach ($source as $item){
            $source = $this->getFixtureRealPath($item);
            $this->assertSourceExists($source);

            $this->fixutreFiles[] = $source;
        }
    }

    /**
     * @param string $source
     *
     * @return array
     */
    protected function persistFixtures($managerName = null)
    {
        $objects = $this->getFixtureLoader($managerName)->load($this->fixutreFiles);
        $this->getEntityManager()->clear();
        $this->fixutreFiles = [];
    }

    /**
     * @param string $source
     *
     * @return array
     */
    protected function loadFixturesFromDirectory($source = '', $managerName = null)
    {
        $source = $this->getFixtureRealPath($source);
        $this->assertSourceExists($source);
        $finder = new Finder();
        $finder->files()->name('*.yml')->in($source);
        if (0 === $finder->count()) {
            throw new \RuntimeException(sprintf('There is no files to load in folder %s', $source));
        }
        $files = [];
        foreach ($finder as $file) {
            $files[] = $file->getRealPath();
        }

        return $this->getFixtureLoader($managerName)->load($files);
    }

    private function getFixtureRealPath(string $source): string
    {
        $baseDirectory = $this->getFixturesFolder();

        return PathBuilder::build($baseDirectory, $source);
    }

    private function getFixturesFolder(): string
    {
        if (null === $this->dataFixturesPath) {
            $this->dataFixturesPath = isset($_SERVER['FIXTURES_DIR']) ?
                PathBuilder::build($this->getRootDir(), $_SERVER['FIXTURES_DIR']) :
                PathBuilder::build($this->getCalledClassFolder(), '..', 'DataFixtures', 'ORM');
        }

        return $this->dataFixturesPath;
    }

    /**
     * @return string
     */
    private function getExpectedResponsesFolder(): string
    {
        if (null === $this->expectedResponsesPath) {
            $this->expectedResponsesPath = isset($_SERVER['EXPECTED_RESPONSE_DIR']) ?
                PathBuilder::build($this->getRootDir(), $_SERVER['EXPECTED_RESPONSE_DIR']) :
                PathBuilder::build($this->getCalledClassFolder(), '..', 'Responses', 'Expected');
        }

        return $this->expectedResponsesPath;
    }

    /**
     * @return string
     */
    protected function getRootDir()
    {
        return $this->getService('kernel')->getRootDir();
    }

    /**
     * @return string
     *
     * @throws \ReflectionException
     */
    protected function getCalledClassFolder()
    {
        $calledClass = get_called_class();
        $calledClassFolder = dirname((new \ReflectionClass($calledClass))->getFileName());
        $this->assertSourceExists($calledClassFolder);

        return $calledClassFolder;
    }

    /**
     * @param string $source
     */
    private function assertSourceExists($source)
    {
        if (!file_exists($source)) {
            throw new \RuntimeException(sprintf('File %s does not exist', $source));
        }
    }

    protected function getService(string $id)
    {
        return self::$kernel->getContainer()
            ->get($id);
    }

    /**
     * @return RegistryInterface
     */
    private function getDoctrine()
    {
        return $this->getService('doctrine');
    }

    /**
     * @return EntityManager
     */
    protected function getEntityManager()
    {
        return $this->getService('doctrine.orm.entity_manager');
    }
}
