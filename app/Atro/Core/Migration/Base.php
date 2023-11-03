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

namespace Atro\Core\Migration;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Comparator;
use Espo\Services\App;
use Espo\Core\Utils\Config;

class Base
{
    private Connection $connection;
    private Config $config;
    private Comparator $comparator;

    public function __construct(Connection $connection, Config $config)
    {
        $this->connection = $connection;
        $this->config = $config;
        $this->comparator = new Comparator();
    }

    public function up(): void
    {
    }

    public function down(): void
    {
    }

    protected function getConnection(): Connection
    {
        return $this->connection;
    }

    protected function getConfig(): Config
    {
        return $this->config;
    }

    protected function getComparator(): Comparator
    {
        return $this->comparator;
    }

    protected function getPDO(): \PDO
    {
        return $this->getConnection()->getWrappedConnection()->getWrappedConnection();
    }

    protected function rebuild()
    {
        App::createRebuildNotification();
    }

    /**
     * @deprecated use rebuild instead
     */
    protected function rebuildByCronJob()
    {
        $this->rebuild();
    }

    protected function updateComposer(string $package, string $version): void
    {
        foreach (['composer.json', 'data/stable-composer.json'] as $filename) {
            if (!file_exists($filename)) {
                continue;
            }
            $data = json_decode(file_get_contents($filename), true);
            $data['require'] = array_merge($data['require'], [$package => $version]);
            file_put_contents($filename, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        }
    }
}
