<?php

namespace SoftPassio\ApiTestCasesBundle\Api\Cases;

use Fidry\AliceDataFixtures\Loader\PurgerLoader;
use Symfony\Bundle\FrameworkBundle\Client;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Finder\Finder;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;

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
     * @var Client
     */
    protected static $staticClient;

    /**
     * @var Client
     */
    protected $client;

    /** @var PurgerLoader */
    private $loader;

    protected function setUp(): void
    {
        $this->dataFixturesPath = __DIR__.'/../Fixtures/ORM';
        $this->expectedResponsesPath = __DIR__.'/../Responses/Expected';
        $this->client = static::createClient();

        $this->setUpDatabase();
    }

    public function setUpDatabase(): void
    {
        $this->purgeDatabase();
    }

    private function purgeDatabase(): void
    {
        /** @var EntityManagerInterface $manager */
        foreach ($this->getDoctrine()->getManagers() as $manager) {
            $purger = new ORMPurger($manager);
            $purger->purge();
        }
    }

    /**
     * @return PurgerLoader
     */
    protected function getFixtureLoader()
    {
        if (!$this->loader) {
            $this->loader = self::$container->get('fidry_alice_data_fixtures.loader.doctrine_mongodb');
        }

        return $this->loader;
    }
    
    /**
     * @param string $source
     *
     * @return array
     */
    protected function loadFixturesFromFile($source)
    {
        $source = $this->getFixtureRealPath($source);
        $this->assertSourceExists($source);

        return $this->getFixtureLoader()->load([$source]);
    }

    /**
     * @param string $source
     * @return array
     */
    protected function loadFixturesFromDirectory(string $source = '')
    {
        $source = $this->getFixtureRealPath($source);
        $this->assertSourceExists($source);
        $finder = new Finder();
        $finder->files()->name('*.yaml')->in($source);
        if (0 === $finder->count()) {
            throw new \RuntimeException(sprintf('There is no files to load in folder %s', $source));
        }
        $files = [];
        foreach ($finder as $file) {
            $files[] = $file->getRealPath();
        }

        return $this->getFixtureLoader()->load($files);
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
                PathBuilder::build($this->getCalledClassFolder(), '..', 'Fixtures', 'ORM');
        }

        return $this->dataFixturesPath;
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
     * @return EntityManager
     */
    protected function getEntityManager()
    {
        return $this->getService('doctrine.orm.entity_manager');
    }
}
