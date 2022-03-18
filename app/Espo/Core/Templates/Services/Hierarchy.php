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
 *
 * This software is not allowed to be used in Russia and Belarus.
 */

declare(strict_types=1);

namespace Espo\Core\Templates\Services;

use Espo\Core\Exceptions\Forbidden;
use Espo\Core\Exceptions\NotFound;
use Espo\Core\Utils\Util;
use Espo\ORM\Entity;
use Espo\ORM\EntityCollection;
use Espo\Services\Record;

class Hierarchy extends Record
{
    public const NON_INHERITED_FIELDS = ['modifiedAt', 'sortOrder', 'createdBy', 'createdBy', 'modifiedBy', 'ownerUser', 'assignedUser'];

    public function getRoute(string $id): array
    {
        return $this
            ->getRepository()
            ->getRoute($id);
    }

    public function getChildren(string $parentId): array
    {
        $result = [];
        foreach ($this->getRepository()->getChildrenArray($parentId) as $record) {
            $result[] = [
                'id'             => $record['id'],
                'name'           => $record['name'],
                'load_on_demand' => !empty($record['childrenCount'])
            ];
        }

        return $result;
    }

    public function getEntity($id = null)
    {
        $entity = parent::getEntity($id);

        if (!empty($entity)) {
            $entity->set('isRoot', $this->getRepository()->isRoot($entity->get('id')));
            $entity->set('inheritedFields', $this->getInheritedFields($entity));
        }

        return $entity;
    }

    public function updateEntity($id, $data)
    {
        if (property_exists($data, '_sortedIds') && property_exists($data, '_id')) {
            $this->getRepository()->updateHierarchySortOrder($data->_id, $data->_sortedIds);
            return $this->getEntity($id);
        }

        if (property_exists($data, '_position') && property_exists($data, '_target') && property_exists($data, 'parentId')) {
            if (empty($entity = $this->getRepository()->get($id))) {
                throw new NotFound();
            }
            if (!$this->getAcl()->check($entity, 'edit')) {
                throw new Forbidden();
            }
            $this->getRepository()->updatePositionInTree((string)$id, (string)$data->_position, (string)$data->_target, (string)$data->parentId);
            return $this->getEntity($id);
        }

        if (property_exists($data, '_fieldValueInheritance') && $data->_fieldValueInheritance) {
            return parent::updateEntity($id, $data);
        }

        if (empty($this->getMetadata()->get(['scopes', $this->entityType, 'fieldValueInheritance']))) {
            return parent::updateEntity($id, $data);
        }

        $this->getEntityManager()->getPDO()->beginTransaction();
        try {
            $entityData = $this->getRepository()->fetchById($id);
            $result = parent::updateEntity($id, $data);
            $this->createPseudoTransactionJobs($entityData, clone $data);
            $this->getEntityManager()->getPDO()->commit();
        } catch (\Throwable $e) {
            $this->getEntityManager()->getPDO()->rollBack();
            throw $e;
        }

        return $result;
    }

    public function deleteEntity($id)
    {
        $this->getEntityManager()->getPDO()->beginTransaction();
        try {
            $result = parent::deleteEntity($id);
            foreach ($this->getRepository()->getChildrenRecursivelyArray($id) as $childId) {
                parent::deleteEntity($childId);
            }
            $this->getEntityManager()->getPDO()->commit();
        } catch (\Throwable $e) {
            $this->getEntityManager()->getPDO()->rollBack();
            throw $e;
        }

        return $result;
    }

    public function prepareEntityForOutput(Entity $entity)
    {
        parent::prepareEntityForOutput($entity);

        if ($entity->has('hierarchySortOrder')) {
            $entity->set('sortOrder', $entity->get('hierarchySortOrder'));
        }
    }

    public function findLinkedEntities($id, $link, $params)
    {
        $result = parent::findLinkedEntities($id, $link, $params);

        if ($link === 'children' && !empty($result['total'])) {
            $result['collection'] = $this->sortCollection($result['collection']);
        }

        return $result;
    }

    protected function createPseudoTransactionJobs(array $parent, \stdClass $data, string $parentTransactionId = null): void
    {
        $children = $this->getRepository()->getChildrenArray($parent['id']);
        foreach ($children as $child) {
            $inputData = $this->createInputDataForPseudoTransactionJob($parent, $child, clone $data);
            if (!empty((array)$inputData)) {
                $inputData->_fieldValueInheritance = true;
                $transactionId = $this->getPseudoTransactionManager()->pushUpdateEntityJob($this->entityType, $child['id'], $inputData, $parentTransactionId);
                if ($child['childrenCount'] > 0) {
                    $this->createPseudoTransactionJobs($child, clone $inputData, $transactionId);
                }
            }
        }
    }

    protected function createInputDataForPseudoTransactionJob(array $parent, array $child, \stdClass $data): \stdClass
    {
        $inputData = new \stdClass();
        foreach ($data as $field => $value) {
            $underScoredField = Util::toUnderScore($field);
            if (!array_key_exists($underScoredField, $parent)) {
                continue 1;
            }
            if ($this->areValuesEqual($this->getRepository()->get(), $field, $parent[$underScoredField], $child[$underScoredField])) {
                $inputData->$field = $value;
            }
        }

        return $inputData;
    }

    protected function sortCollection(EntityCollection $inputCollection): EntityCollection
    {
        $ids = [];
        foreach ($inputCollection as $entity) {
            $ids[$entity->get('id')] = $entity->get('sortOrder');
        }
        asort($ids);

        $collection = new EntityCollection();
        foreach ($ids as $id => $sortOrder) {
            foreach ($inputCollection as $entity) {
                if ($entity->get('id') === $id) {
                    $collection->append($entity);
                    break;
                }
            }
        }

        return $collection;
    }

    protected function getInheritedFields(Entity $entity): array
    {
        $inheritedFields = [];
        if (!empty($parents = $entity->get('parents')) && count($parents) > 0) {
            foreach ($parents as $parent) {
                foreach ($this->getMetadata()->get(['entityDefs', $this->entityType, 'fields'], []) as $field => $fieldData) {
                    if (in_array($field, $inheritedFields) || in_array($field, self::NON_INHERITED_FIELDS)) {
                        continue 1;
                    }

                    if ($fieldData['type'] === 'linkMultiple' || !empty($fieldData['notStorable'])) {
                        continue 1;
                    }

                    $fieldKey = $field;

                    if ($fieldData['type'] === 'link') {
                        $fieldKey .= 'Id';
                    }
                    if ($this->areValuesEqual($this->getRepository()->get(), $field, $parent->get($fieldKey), $entity->get($fieldKey))) {
                        $inheritedFields[] = $field;
                    }
                }
            }
        }

        return $inheritedFields;
    }
}
