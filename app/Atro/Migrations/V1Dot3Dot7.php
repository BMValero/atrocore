<?php
/**
* AtroCore Software
*
* This source file is available under GNU General Public License version 3 (GPLv3).
* Full copyright and license information is available in LICENSE.md, located in the root directory.
*
*  @copyright  Copyright (c) AtroCore UG (https://www.atrocore.com)
*  @license    GPLv3 (https://www.gnu.org/licenses/)
*/

declare(strict_types=1);

namespace Atro\Migrations;

use Atro\Core\Migration\Base;

class V1Dot3Dot7 extends Base
{
    public function up(): void
    {
        try {
            $container = (new \Espo\Core\Application())->getContainer();
            $container->get('dataManager')->rebuildScheduledJobs();
        } catch (\Throwable $e) {
            // ignore all errors
        }
    }

    public function down(): void
    {
    }
}
