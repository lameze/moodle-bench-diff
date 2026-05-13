<?php
namespace App\Service;

use App\Model\Dataset;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\ParameterBag\ContainerBagInterface;

class HybridDatasetLoader implements
    CachingDatasetLoaderInterface,
    DatasetLoaderInterface
{
    public function __construct(
        private FilebasedDatasetLoader $filebasedLoader,
        private ?S3DatasetLoader $s3Loader,
        private ContainerBagInterface $params,
    ) {
    }

    /**
     * Determine if the path is a local file path
     */
    private function isLocalFilePath(string $path): bool
    {
        // Check if it's an absolute path
        if (str_starts_with($path, '/')) {
            return true;
        }

        // Check if it's a relative path
        if (str_starts_with($path, './') || str_starts_with($path, '../')) {
            return true;
        }

        // Check if it's a file that exists
        if (file_exists($path)) {
            return true;
        }

        return false;
    }

    /**
     * Get the appropriate loader for the given path
     */
    private function getLoader(string $path): DatasetLoaderInterface
    {
        if ($this->isLocalFilePath($path)) {
            return $this->filebasedLoader;
        }

        // If S3Loader is not available, try FilebasedDatasetLoader as fallback
        if ($this->s3Loader === null) {
            return $this->filebasedLoader;
        }

        return $this->s3Loader;
    }

    public function datasetExists(string $name): bool
    {
        $loader = $this->getLoader($name);
        return $loader->datasetExists($name);
    }

    public function loadFullDataset(
        string $name,
        bool $pathIsResolved = false,
    ): Dataset {
        $loader = $this->getLoader($name);
        
        // FilebasedDatasetLoader doesn't have pathIsResolved parameter
        if ($loader instanceof FilebasedDatasetLoader) {
            return $loader->loadFullDataset($name);
        }

        // S3DatasetLoader supports pathIsResolved parameter
        if ($loader instanceof S3DatasetLoader) {
            return $loader->loadFullDataset($name, $pathIsResolved);
        }

        return $loader->loadFullDataset($name);
    }

    public function listDatasets(
        string $matching = '',
        ?SymfonyStyle $io = null,
    ): array {
        if ($this->s3Loader instanceof CachingDatasetLoaderInterface) {
            return $this->s3Loader->listDatasets($matching, $io);
        }

        return [];
    }
}

