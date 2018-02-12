<?php

namespace TickTackk\DeveloperTools\XF;

use XF\Mvc\Entity\Entity;
use XF\Util\Json;
use \TickTackk\DeveloperTools\Git\GitRepository;

class DevelopmentOutput extends XFCP_DevelopmentOutput
{
    protected $repoSuffix = '_repo';

    public function export(Entity $entity)
    {
        $response = parent::export($entity);

        $this->commitOutput('export', $entity);

        return $response;
    }

    public function delete(Entity $entity, $new = true)
    {
        $response = parent::delete($entity, $new);

        $this->commitOutput('delete', $entity);

        return $response;
    }

    public function writeFileToRepo($typeDir, $addOnId, $fileName, $fileContents, array $metadata = [], $verifyChange = true)
    {
        if (!$this->enabled || $this->isAddOnSkipped($addOnId))
        {
            return false;
        }

        $fullPathForRepo = $this->getFilePathForRepo($typeDir, $addOnId, $fileName);

        if ($verifyChange)
        {
            if (!file_exists($fullPathForRepo))
            {
                $write = true;
            }
            else
            {
                $write = file_get_contents($fullPathForRepo) != $fileContents;
            }
        }
        else
        {
            $write = true;
        }

        if ($write)
        {
            \XF\Util\File::writeFile($fullPathForRepo, $fileContents, false);
        }

        $metadata['hash'] = $this->hashContents($fileContents);
        $this->updateMetadata($typeDir, $addOnId, $fileName, $metadata);

        return true;
    }

    public function writeFile($typeDir, $addOnId, $fileName, $fileContents, array $metadata = [], $verifyChange = true)
    {
        $response = parent::writeFile($typeDir, $addOnId, $fileName, $fileContents, $metadata, $verifyChange);

        $this->writeFileToRepo($typeDir, $addOnId, $fileName, $fileContents, $metadata, $verifyChange);

        return $response;
    }

    public function deleteFileFromRepo($typeDir, $addOnId, $fileName)
    {
        if (!$this->enabled || $this->isAddOnSkipped($addOnId))
        {
            return false;
        }

        $fullPath = $this->getFilePathForRepo($typeDir, $addOnId, $fileName);
        if (file_exists($fullPath))
        {
            unlink($fullPath);
        }

        $this->removeMetadata($typeDir, $addOnId, $fileName);

        return true;
    }

    public function deleteFile($typeDir, $addOnId, $fileName)
    {
        $reponse = parent::deleteFile($typeDir, $addOnId, $fileName);

        $this->deleteFileFromRepo($typeDir, $addOnId, $fileName);

        return $reponse;
    }

    public function getFilePathForRepo($typeDir, $addOnId, $fileName)
    {
        $ds = \DIRECTORY_SEPARATOR;
        $addOnIdDir = $this->prepareAddOnIdForPath($addOnId);
        return "{$this->basePath}{$ds}{$addOnIdDir}{$ds}{$this->repoSuffix}{$ds}upload{$ds}src{$ds}addons{$ds}{$addOnIdDir}{$ds}_output{$ds}{$typeDir}{$ds}{$fileName}";
    }

    protected function getMetadataFileNameForRepo($typeDir, $addOnId)
    {
        $ds = DIRECTORY_SEPARATOR;
        $addOnIdDir = $this->prepareAddOnIdForPath($addOnId);
        return "{$this->basePath}{$ds}{$addOnIdDir}{$ds}{$this->repoSuffix}{$ds}upload{$ds}src{$ds}addons{$ds}{$addOnIdDir}{$ds}_output{$ds}{$typeDir}{$ds}{$this->metadataFilename}";
    }

    protected function writeTypeMetadataForRepo($typeDir, $addOnId, array $typeMetadata)
    {
        if ($this->isAddOnSkipped($addOnId))
        {
            return;
        }

        ksort($typeMetadata);

        $this->metadataCache[$typeDir][$addOnId] = $typeMetadata;

        if ($this->batchMode)
        {
            $this->batchesPending[$typeDir][$addOnId] = true;
        }
        else
        {
            $metadataPath = $this->getMetadataFileNameForRepo($typeDir, $addOnId);
            file_put_contents($metadataPath, Json::jsonEncodePretty($typeMetadata));
        }
    }

    protected function writeTypeMetadata($typeDir, $addOnId, array $typeMetadata)
    {
        parent::writeTypeMetadata($typeDir, $addOnId, $typeMetadata);
        $this->writeTypeMetadataForRepo($typeDir, $addOnId, $typeMetadata);
    }

    public function cloneEntity(Entity $entity)
    {
        /** @var \TickTackk\DeveloperTools\XF\DevelopmentOutput\CommitTrait $handler */
        $handler = $this->getHandler($entity->structure()->shortName);

        if (method_exists($handler, 'commitRepo') &&
            method_exists($handler, 'getOutputCommitData'))
        {
            $outputDataKeys = $handler->getOutputCommitData($entity);
            if (empty($outputDataKeys))
            {
                return;
            }

            $handler->cloneEntity($entity, $outputDataKeys);
        }
    }

    protected function getRepoPath(Entity $entity)
    {
        $ds = DIRECTORY_SEPARATOR;
        return $this->basePath . $ds . $this->prepareAddOnIdForPath($entity->addon_id) . $ds . '_repo';
    }

    protected function commitOutput($actionType, Entity $entity)
    {
        $repoDir = $this->getRepoPath($entity);

        if (is_dir($repoDir))
        {
            $git = new GitRepository($repoDir);

            if (!$this->batchMode && $git->isInitialized())
            {
                /** @var \TickTackk\DeveloperTools\XF\DevelopmentOutput\CommitTrait $handler */
                $handler = $this->getHandler($entity->structure()->shortName);

                if (method_exists($handler, 'commitRepo') &&
                    method_exists($handler, 'getOutputCommitData'))
                {
                    $jobId = $entity->structure()->shortName . '_' . $actionType . '_' . $entity->getEntityId();

                    $handler->commitRepo($jobId, $repoDir, $actionType, $entity->isUpdate());
                }
            }
        }
    }
}