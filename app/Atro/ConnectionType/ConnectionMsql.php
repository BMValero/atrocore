<?php
/**
 * AtroCore Software
 *
 * This source file is available under GNU General Public License version 3 (GPLv3).
 * Full copyright and license information is available in LICENSE.md, located in the root directory.
 *
 * @copyright  Copyright (c) AtroCore UG (https://www.atrocore.com)
 * @license    GPLv3 (https://www.gnu.org/licenses/)
 */

declare(strict_types=1);

namespace Atro\ConnectionType;

use Espo\Core\Exceptions\BadRequest;
use Espo\ORM\Entity;

class ConnectionMsql extends AbstractConnection
{
    public function connect(Entity $connection)
    {
        if (!function_exists('sqlsrv_connect')) {
            throw new BadRequest($this->exception('sqlsrvMissing'));
        }

        $serverName = "{$connection->get('host')},{$connection->get('port')}";
        $connectionInfo = [
            "Database"     => $connection->get('dbName'),
            "Uid"          => $connection->get('user'),
            "PWD"          => $this->decryptPassword($connection->get('password')),
            "LoginTimeout" => 5
        ];
        $result = \sqlsrv_connect($serverName, $connectionInfo);

        if ($result === false) {
            throw new BadRequest(
                sprintf($this->exception('connectionFailed'), implode(', ', array_column(\sqlsrv_errors(), 'message')))
            );
        }

        return $result;
    }
}