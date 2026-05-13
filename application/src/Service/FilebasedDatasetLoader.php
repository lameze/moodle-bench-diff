<?php
namespace App\Service;

use App\Model\Dataset;
use Symfony\Component\DependencyInjection\ParameterBag\ContainerBagInterface;

class FilebasedDatasetLoader implements DatasetLoaderInterface
{
    public function __construct(
        private ContainerBagInterface $params,
    ) {
    }

    private function validateDatasetName(
        string $name,
    ): void {
        if (str_contains($name, '..')) {
            throw new \InvalidArgumentException('Invalid dataset name');
        }
    }

    /**
     * Check if the given path is an absolute file path
     */
    private function isAbsolutePath(string $path): bool
    {
        return str_starts_with($path, '/') || str_starts_with($path, './');
    }

    private function getDatasetPath(
        string $name,
    ): string {
        // If it's an absolute path, use it directly
        if ($this->isAbsolutePath($name)) {
            return $name;
        }

        $this->validateDatasetName($name);
        $filename = sprintf('%s.json', $name);

        return $this->params->get('app.datasets_path') . '/' . $filename;
    }

    public function datasetExists(
        string $name,
    ): bool {
        $path = $this->getDatasetPath($name);

        return file_exists($path) && is_readable($path);
    }

    public function loadFullDataset(
        string $name,
    ): Dataset {
        $path = $this->getDatasetPath($name);

        if (!file_exists($path)) {
            throw new \InvalidArgumentException('Dataset not found');
        }

        if (!is_readable($path)) {
            throw new \RuntimeException('Dataset is not readable');
        }

        $data = json_decode(file_get_contents($path));

        // Add runTime if it doesn't exist (use file modification time or current time)
        if (!isset($data->runTime)) {
            $data->runTime = \DateTimeImmutable::createFromFormat('U', (string)filemtime($path));
        }

        return Dataset::loadFullDataset($name, $data);
    }
}
