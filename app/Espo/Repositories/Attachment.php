<?php
/*
 * This file is part of EspoCRM and/or AtroCore.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2019 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
 * Website: http://www.espocrm.com
 *
 * AtroCore is EspoCRM-based Open Source application.
 * Copyright (C) 2020 AtroCore UG (haftungsbeschränkt).
 *
 * AtroCore as well as EspoCRM is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * AtroCore as well as EspoCRM is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with EspoCRM. If not, see http://www.gnu.org/licenses/.
 *
 * The interactive user interfaces in modified source and object code versions
 * of this program must display Appropriate Legal Notices, as required under
 * Section 5 of the GNU General Public License version 3.
 *
 * In accordance with Section 7(b) of the GNU General Public License version 3,
 * these Appropriate Legal Notices must retain the display of the "EspoCRM" word
 * and "AtroCore" word.
 */

declare(strict_types=1);

namespace Espo\Repositories;

use Espo\Core\Exceptions\BadRequest;
use Espo\ORM\Entity;
use Treo\Core\FilePathBuilder;
use Treo\Core\FileStorage\Storages\UploadDir;
use Treo\Core\Utils\Config;
use Treo\Core\Utils\Util;

/**
 * Class Attachment
 */
class Attachment extends \Espo\Core\ORM\Repositories\RDB
{
    /**
     * @inheritDoc
     */
    public function beforeSave(Entity $entity, array $options = array())
    {
        parent::beforeSave($entity, $options);

        $storage = $entity->get('storage');
        if (!$storage) {
            $entity->set('storage', $this->getConfig()->get('defaultFileStorage', null));
        }

        if ($entity->isNew()) {
            if (!$entity->has("contents")) {
                throw new BadRequest($this->translate('File uploading failed.', 'exceptions', 'Attachment'));
            }

            $contents = $entity->get('contents');

            $entity->set('md5', md5($contents));
            if (!$entity->has('size')) {
                $entity->set('size', mb_strlen($contents));
            }
        }
    }

    /**
     * @param Entity $entity
     * @param null   $role
     *
     * @return Entity
     */
    public function getCopiedAttachment(Entity $entity, $role = null)
    {
        $attachment = $this->get();

        $attachment->set(
            [
                'sourceId'        => $entity->getSourceId(),
                'name'            => $entity->get('name'),
                'type'            => $entity->get('type'),
                'size'            => $entity->get('size'),
                'role'            => $entity->get('role'),
                'storageFilePath' => $entity->get('storageFilePath'),
                'relatedType'     => $entity->get('relatedType'),
                'relatedId'       => $entity->get('relatedId'),
                'md5'             => $entity->get('md5')
            ]
        );

        if ($role) {
            $attachment->set('role', $role);
        }

        $this->save($attachment);

        return $attachment;
    }

    /**
     * @param Entity $entity
     *
     * @return string
     */
    public function copy(Entity $entity): string
    {
        $source = $this->where(["id" => $entity->get('sourceId')])->findOne();

        $sourcePath = $this->getFilePath($source);
        $destPath = $this->getDestPath(FilePathBuilder::UPLOAD);
        $fullDestPath = UploadDir::BASE_PATH . $destPath;

        if ($this->getFileManager()->copy($sourcePath, $fullDestPath, false, null, true)) {
            return $destPath;
        }

        return '';
    }

    /**
     * @inheritDoc
     */
    public function save(Entity $entity, array $options = [])
    {
        $isNew = $entity->isNew();

        if ($isNew) {
            if (!$entity->has("id")) {
                $entity->id = Util::generateId();
            }
            $storeResult = false;

            if (!empty($entity->id) && $entity->has('contents')) {
                $contents = $entity->get('contents');
                if ($entity->get('role') === "Attachment") {
                    $temp = $this->getFileManager()->createOnTemp($contents);
                    if ($temp) {
                        $entity->set("tmpPath", $temp);
                        $storeResult = true;
                    }
                } else {
                    $storeResult = $this->getFileStorageManager()->putContents($entity, $contents);
                }
                if ($storeResult === false) {
                    throw new \Espo\Core\Exceptions\Error("Could not store the file");
                }
            }
        }

        return parent::save($entity, $options);
    }

    /**
     * @param Entity $entity
     *
     * @return bool
     */
    public function moveFromTmp(Entity $entity)
    {
        $destPath = $this->getDestPath(FilePathBuilder::UPLOAD);
        $fullPath = UploadDir::BASE_PATH . $destPath . "/" . $entity->get('name');

        if ($this->getFileManager()->move($entity->get('tmpPath'), $fullPath, false)) {
            $entity->set("tmpPath", null);
            $entity->set("storageFilePath", $destPath);

            return true;
        }

        return false;
    }

    /**
     * @inheritDoc
     */
    protected function init()
    {
        parent::init();

        $this->addDependency('container');
        $this->addDependency('config');
        $this->addDependency('language');
        $this->addDependency('fileStorageManager');
        $this->addDependency('filePathBuilder');
        $this->addDependency('fileManager');
    }


    /**
     * @inheritDoc
     */
    protected function afterRemove(Entity $entity, array $options = array())
    {
        parent::afterRemove($entity, $options);

        $duplicateCount = $this->where(['OR' => [['sourceId' => $entity->getSourceId()], ['id' => $entity->getSourceId()]],])->count();
        if ($duplicateCount === 0) {
            $this->getFileStorageManager()->unlink($entity);
        }
    }

    /**
     * @param Entity $entity
     *
     * @return string|null
     */
    public function getContents(Entity $entity): ?string
    {
        return $this->getFileStorageManager()->getContents($entity);
    }

    /**
     * @param Entity $entity
     *
     * @return string|null
     */
    public function getFilePath(Entity $entity): ?string
    {
        return $this->getFileStorageManager()->getLocalFilePath($entity);
    }

    /**
     * @param Entity $entity
     *
     * @return bool
     */
    public function hasDownloadUrl(Entity $entity): bool
    {
        return $this->getFileStorageManager()->hasDownloadUrl($entity);
    }

    /**
     * @param Entity $entity
     *
     * @return string|null
     */
    public function getDownloadUrl(Entity $entity): ?string
    {
        return $this->getFileStorageManager()->getDownloadUrl($entity);
    }

    /**
     * @return Config
     */
    protected function getConfig()
    {
        return $this->getInjection('config');
    }

    /**
     * @param string $key
     * @param string $label
     * @param string $scope
     *
     * @return string
     */
    protected function translate(string $key, string $label, $scope = 'Global'): string
    {
        return $this->getInjection('language')->translate($key, $label, $scope);
    }

    /**
     * @return \Treo\Core\FileStorage\Manager
     */
    protected function getFileStorageManager()
    {
        return $this->getInjection('fileStorageManager');
    }

    /**
     * @return FilePathBuilder
     */
    protected function getPathBuilder()
    {
        return $this->getInjection('filePathBuilder');
    }

    /**
     * @return \Treo\Core\Utils\File\Manager
     */
    protected function getFileManager()
    {
        return $this->getInjection('fileManager');
    }

    /**
     * @param string $type
     *
     * @return string
     */
    protected function getDestPath(string $type): string
    {
        return $this->getPathBuilder()->createPath($type);
    }
}
