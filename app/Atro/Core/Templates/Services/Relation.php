<?php
/**
 * AtroCore Software
 *
 * This source file is available under GNU General Public License version 3 (GPLv3).
 * Full copyright and license information is available in LICENSE.txt, located in the root directory.
 *
 * @copyright  Copyright (c) AtroCore UG (https://www.atrocore.com)
 * @license    GPLv3 (https://www.gnu.org/licenses/)
 */

declare(strict_types=1);

namespace Atro\Core\Templates\Services;

use Espo\Core\Exceptions\BadRequest;
use Espo\Core\Exceptions\Forbidden;
use Espo\Core\Exceptions\NotFound;
use Espo\ORM\Entity;
use Espo\Services\Record;

class Relation extends Record
{
    protected function afterCreateEntity(Entity $entity, $data)
    {
        $this->createHierarchical($entity);

        parent::afterCreateEntity($entity, $data);
    }

    public function createHierarchical(Entity $entity): void
    {
        $link = $this->getRepository()->getHierarchicalRelation();
        if (empty($link)) {
            return;
        }

        $hierarchicalEntity = $entity->get($link);
        if (empty($hierarchicalEntity)) {
            return;
        }

        $children = $this->getEntityManager()->getRepository($hierarchicalEntity->getEntityType())->getChildrenRecursivelyArray($hierarchicalEntity->get('id'));
        if (empty($children)) {
            return;
        }

        $additionalFields = $this->getRepository()->getAdditionalFieldsNames();

        foreach ($children as $childId) {
            $input = new \stdClass();
            foreach ($this->getRepository()->getRelationFields() as $relField) {
                $input->{"{$relField}Id"} = $relField === $link ? $childId : $entity->get("{$relField}Id");
            }
            foreach ($additionalFields as $additionalField) {
                $input->{$additionalField} = $entity->get($additionalField);
            }
            $parentId = $this->getPseudoTransactionManager()->pushCreateEntityJob($entity->getEntityType(), $input);
            $this->getPseudoTransactionManager()->pushUpdateEntityJob($hierarchicalEntity->getEntityType(), $hierarchicalEntity->get('id'), null, $parentId);
        }
    }

    protected function afterDeleteEntity(Entity $entity)
    {
        $this->deleteHierarchical($entity);

        parent::afterDeleteEntity($entity);
    }

    public function deleteHierarchical(Entity $entity): void
    {
        $link = $this->getRepository()->getHierarchicalRelation();
        if (empty($link)) {
            return;
        }

        $hierarchicalEntity = $entity->get($link);
        if (empty($hierarchicalEntity)) {
            return;
        }

        $childrenIds = $hierarchicalEntity->getLinkMultipleIdList('children');
        if (empty($childrenIds[0])) {
            return;
        }

        $additionalFields = $this->getRepository()->getAdditionalFieldsNames();

        $where = [];
        foreach ($childrenIds as $childId) {
            foreach ($this->getRepository()->getRelationFields() as $relField) {
                if ($relField === $link) {
                    $where["{$relField}Id"][] = $childId;
                } else {
                    $where["{$relField}Id"] = $entity->get("{$relField}Id");
                }
            }
            foreach ($additionalFields as $additionalField) {
                $where[$additionalField] = $entity->get($additionalField);
            }
        }

        $childrenRecords = $this->getRepository()->select(['id'])->where($where)->find();
        foreach ($childrenRecords as $childrenRecord) {
            try {
                $this->deleteEntity($childrenRecord->get('id'));
            } catch (Forbidden $e) {
            } catch (NotFound $e) {
            } catch (BadRequest $e) {
            }
        }
    }
}
