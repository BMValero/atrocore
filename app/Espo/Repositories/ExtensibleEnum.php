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

use Espo\Core\Templates\Repositories\Base;
use Espo\ORM\Entity;

class ExtensibleEnum extends Base
{
    protected function beforeSave(Entity $entity, array $options = [])
    {
        if ($entity->get('code') === '') {
            $entity->set('code', null);
        }

        parent::beforeSave($entity, $options);

        if ($entity->isAttributeChanged('multilingual') && empty($entity->get('multilingual'))) {
            $this->clearLingualOptions($entity);
        }
    }

    protected function afterRemove(Entity $entity, array $options = [])
    {
        $this->getEntityManager()->getRepository('ExtensibleEnumOption')->where(['extensibleEnumId' => $entity->get('id')])->removeCollection();

        parent::afterRemove($entity, $options);
    }

    public function clearLingualOptions(Entity $entity): void
    {
        $fields = $this->getEntityManager()->getRepository('ExtensibleEnumOption')->getLingualFields();
        if (empty($fields)) {
            return;
        }

        foreach ($entity->get('extensibleEnumOptions') as $option) {
            foreach ($fields as $field) {
                $option->set($field, null);
            }
            $this->getEntityManager()->saveEntity($option);
        }
    }
}
