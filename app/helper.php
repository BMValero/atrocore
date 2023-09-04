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

class_alias("\\Atro\\Composer\\PostUpdate", "\\Treo\\Composer\\PostUpdate");
class_alias("\\Atro\\Core\\Migration\\Base", "\\Treo\\Core\\Migration\\Base");
class_alias("\\Atro\\Core\\FileStorage\\Storages\\Base", "\\Treo\\Core\\FileStorage\\Storages\\Base");
class_alias("\\Atro\\Core\\FileStorage\\Storages\\UploadDir", "\\Treo\\Core\\FileStorage\\Storages\\UploadDir");
class_alias("\\Atro\\Core\\Exceptions\\NotModified", "\\Treo\\Core\\Exceptions\\NotModified");
class_alias("\\Atro\\Core\\ModuleManager\\AbstractEvent", "\\Treo\\Core\\ModuleManager\\AbstractEvent");
class_alias("\\Atro\\Core\\ModuleManager\\AbstractModule", "\\Treo\\Core\\ModuleManager\\AbstractModule");
class_alias("\\Atro\\Core\\ModuleManager\\AfterInstallAfterDelete", "\\Treo\\Core\\ModuleManager\\AfterInstallAfterDelete");
class_alias("\\Atro\\Core\\ModuleManager\\Manager", "\\Treo\\Core\\ModuleManager\\Manager");
class_alias("\\Atro\\Core\\Utils\\Condition\\Condition", "\\Treo\\Core\\Utils\\Condition\\Condition");
class_alias("\\Atro\\Core\\Utils\\Condition\\ConditionGroup", "\\Treo\\Core\\Utils\\Condition\\ConditionGroup");
class_alias("\\Atro\\Core\\Utils\\Database\\Schema\\Schema", "\\Treo\\Core\\Utils\\Database\\Schema\\Schema");
class_alias("\\Atro\\Core\\Container", "\\Espo\\Core\\Container");
class_alias("\\Atro\\Core\\Application", "\\Espo\\Core\\Application");
class_alias("\\Atro\\Core\\Twig\\AbstractTwigFilter", "\\Espo\\Core\\Twig\\AbstractTwigFilter");
class_alias("\\Atro\\Core\\Twig\\AbstractTwigFunction", "\\Espo\\Core\\Twig\\AbstractTwigFunction");

$migrations = [
    'V1Dot2Dot0', 'V1Dot2Dot43', 'V1Dot2Dot61', 'V1Dot3Dot32', 'V1Dot3Dot40', 'V1Dot4Dot0', 'V1Dot4Dot14', 'V1Dot4Dot40', 'V1Dot4Dot70', 'V1Dot5Dot0', 'V1Dot5Dot30',
    'V1Dot5Dot64', 'V1Dot6Dot8', 'V1Dot2Dot17', 'V1Dot2Dot44', 'V1Dot3Dot12', 'V1Dot3Dot35', 'V1Dot3Dot42', 'V1Dot4Dot126', 'V1Dot4Dot15', 'V1Dot4Dot41', 'V1Dot4Dot82',
    'V1Dot5Dot27', 'V1Dot5Dot31', 'V1Dot6Dot0', 'V1Dot2Dot26', 'V1Dot2Dot56', 'V1Dot3Dot26', 'V1Dot3Dot37', 'V1Dot3Dot43', 'V1Dot4Dot131', 'V1Dot4Dot17', 'V1Dot4Dot66',
    'V1Dot4Dot91', 'V1Dot5Dot28', 'V1Dot5Dot39', 'V1Dot6Dot1', 'V1Dot2Dot41', 'V1Dot2Dot57', 'V1Dot3Dot2', 'V1Dot3Dot3', 'V1Dot3Dot7', 'V1Dot4Dot13', 'V1Dot4Dot1',
    'V1Dot4Dot69', 'V1Dot4Dot95', 'V1Dot5Dot29', 'V1Dot5Dot56', 'V1Dot6Dot22'
];
foreach ($migrations as $migration) {
    class_alias("\\Atro\\Migrations\\$migration", "\\Treo\\Migrations\\$migration");
}
