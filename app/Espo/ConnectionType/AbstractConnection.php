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

namespace Espo\ConnectionType;

use Espo\Core\Interfaces\Injectable;

abstract class AbstractConnection implements ConnectionInterface, Injectable
{
    protected array $dependencies = ['config', 'entityManager', 'user', 'language', 'serviceFactory'];
    protected array $injections = [];

    /**
     * @param string $name
     * @param mixed  $object
     *
     * @return void
     */
    public function inject($name, $object)
    {
        $this->injections[$name] = $object;
    }

    /**
     * @param string $name
     *
     * @return mixed
     */
    protected function getInjection(string $name)
    {
        return $this->injections[$name];
    }

    /**
     * @return string[]
     */
    public function getDependencyList()
    {
        return $this->dependencies;
    }

    protected function addDependency($name): void
    {
        $this->dependencies[] = $name;
    }

    protected function decryptPassword(string $hash): string
    {
        return $this->getInjection('serviceFactory')->create('Connection')->decryptPassword($hash);
    }
}
