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

namespace Atro\Services;

use Espo\Core\Templates\Services\Base;

class Action extends Base
{
    protected $mandatorySelectAttributeList = ['data'];

    public function executeNow(\stdClass $input): bool
    {
        echo '<pre>';
        print_r($input);
        die();

        $action = $this->getRepository()->get($id);

        echo '<pre>';
        print_r($action->toArray());
        die();
    }
}
