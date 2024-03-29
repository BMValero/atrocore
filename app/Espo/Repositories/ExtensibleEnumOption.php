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
use Espo\Core\Templates\Repositories\Base;
use Espo\Core\Utils\Util;
use Espo\ORM\Entity;

class ExtensibleEnumOption extends Base
{
    protected array $cachedOptions = [];

    public function getPreparedOption(string $extensibleEnumId, ?string $id): ?array
    {
        if ($id === null || $id === '') {
            return null;
        }

        $options = $this->getPreparedOptions($extensibleEnumId, [$id]);

        return $options[0] ?? null;
    }

    public function getPreparedOptions(string $extensibleEnumId, ?array $ids): ?array
    {
        if (!is_array($ids)) {
            return null;
        }

        $res = [];
        foreach ($ids as $id) {
            $id = (string)$id;
            if ($id === '') {
                continue;
            }

            if (!isset($this->cachedOptions[$id])) {
                $this->cachedOptions[$id] = [
                    'id'                => $id,
                    'name'              => $id,
                    'preparedName'      => $id,
                    'notExistingOption' => true
                ];

                // prepare select
                $select = ['id', 'code', 'color', 'name','sortOrder'];
                foreach ($this->getLingualFields('name') as $lingualField) {
                    $select[] = $lingualField;
                }
                if ($this->getMetadata()->get(['entityDefs', 'ExtensibleEnumOption', 'fields', 'description'])) {
                    $select[] = 'description';
                }
                foreach ($this->select($select)->order('sortOrder')->where(['extensibleEnumId' => $extensibleEnumId])->find() as $item) {
                    $row = $item->toArray();
                    $row['preparedName'] = $row[$this->getOptionName()];
                    $this->cachedOptions[$item->get('id')] = $row;
                }
            }
            $res[] = $this->cachedOptions[$id];
        }

        if(count($res) > 1){
            usort($res, function($option1, $option2){
                return ($option1['sortOrder'] < $option2['sortOrder']) ?  -1 : 1;
            });
        }
        return $res;
    }

    protected function beforeSave(Entity $entity, array $options = [])
    {
        if ($entity->get('code') === '') {
            $entity->set('code', null);
        }

        if ($entity->isNew() && $entity->get('sortOrder') === null) {
            $entity->set('sortOrder', time() - (new \DateTime('2023-01-01'))->getTimestamp());
        }

        $extensibleEnum = $entity->get('extensibleEnum');

        if (!empty($extensibleEnum)) {
            foreach ($this->getLingualFields() as $field) {
                if ($entity->isAttributeChanged($field) && $entity->get($field) !== null && empty($extensibleEnum->get('multilingual'))) {
                    throw new BadRequest("List '{$extensibleEnum->get('name')}' is not multilingual.");
                }
            }
        }

        parent::beforeSave($entity, $options);
    }

    public function updateSortOrder(array $ids): void
    {
        $collection = $this->where(['id' => $ids])->find();
        if (empty($collection[0])) {
            return;
        }

        foreach ($ids as $k => $id) {
            $sortOrder = (int)$k * 10;
            foreach ($collection as $entity) {
                if ($entity->get('id') !== (string)$id) {
                    continue;
                }
                $entity->set('sortOrder', $sortOrder);
                $this->save($entity);
            }
        }
    }

    public function getLingualFields(string $fieldName = 'name'): array
    {
        $names = [];
        foreach ($this->getMetadata()->get(['entityDefs', 'ExtensibleEnumOption', 'fields']) as $field => $fieldDefs) {
            if (!empty($fieldDefs['multilangField']) && $fieldDefs['multilangField'] === $fieldName) {
                $names[] = $field;
            }
        }

        return $names;
    }

    protected function getOptionName(): string
    {
        $language = \Espo\Core\Services\Base::getLanguagePrism();
        if (!empty($language) && $language !== 'main') {
            if ($this->getConfig()->get('isMultilangActive') && in_array($language, $this->getConfig()->get('inputLanguageList', []))) {
                return Util::toCamelCase('name_' . strtolower($language));
            }
        }

        return 'name';
    }
}
