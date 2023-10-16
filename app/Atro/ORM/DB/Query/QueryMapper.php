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

namespace Atro\ORM\DB\Query;

use Doctrine\DBAL\Connection;
use Espo\ORM\IEntity;
use Espo\ORM\EntityFactory;

class QueryMapper
{
    public const TABLE_ALIAS = 't1';

    protected static array $selectParamList
        = [
            'select',
            'whereClause',
            'offset',
            'limit',
            'order',
            'orderBy',
            'customWhere',
            'customJoin',
            'joins',
            'leftJoins',
            'distinct',
            'joinConditions',
            'aggregation',
            'aggregationBy',
            'groupBy',
            'havingClause',
            'customHaving',
            'skipTextColumns',
            'maxTextColumnsLength'
        ];

    protected static array $sqlOperators
        = [
            'OR',
            'AND',
        ];

    protected static array $comparisonOperators
        = [
            '!=s' => 'NOT IN',
            '=s'  => 'IN',
            '!='  => '<>',
            '!*'  => 'NOT LIKE',
            '*'   => 'LIKE',
            '>='  => '>=',
            '<='  => '<=',
            '>'   => '>',
            '<'   => '<',
            '='   => '='
        ];

    protected array $functionList
        = [
            'COUNT',
            'SUM',
            'AVG',
            'MAX',
            'MIN',
            'MONTH',
            'DAY',
            'YEAR',
            'WEEK',
            'WEEK_0',
            'WEEK_1',
            'DAYOFMONTH',
            'DAYOFWEEK',
            'DAYOFWEEK_NUMBER',
            'MONTH_NUMBER',
            'DATE_NUMBER',
            'YEAR_NUMBER',
            'HOUR_NUMBER',
            'HOUR',
            'MINUTE_NUMBER',
            'MINUTE',
            'WEEK_NUMBER',
            'WEEK_NUMBER_0',
            'WEEK_NUMBER_1',
            'LOWER',
            'UPPER',
            'TRIM',
            'LENGTH'
        ];

    protected array $matchFunctionList = ['MATCH_BOOLEAN', 'MATCH_NATURAL_LANGUAGE', 'MATCH_QUERY_EXPANSION'];

    protected array $matchFunctionMap
        = [
            'MATCH_BOOLEAN'          => 'IN BOOLEAN MODE',
            'MATCH_NATURAL_LANGUAGE' => 'IN NATURAL LANGUAGE MODE',
            'MATCH_QUERY_EXPANSION'  => 'WITH QUERY EXPANSION'
        ];

    protected EntityFactory $entityFactory;
    protected Connection $connection;

    protected array $fieldsMapCache = [];
    protected array $aliasesCache = [];
    protected array $seedCache = [];
    protected array $parameters = [];

    public function __construct(EntityFactory $entityFactory, Connection $connection)
    {
        $this->entityFactory = $entityFactory;
        $this->connection = $connection;
    }

    public function createSelectQuery(string $entityType, array $params = [], bool $deleted = false): array
    {
        $entity = $this->getSeed($entityType);

        foreach (self::$selectParamList as $k) {
            $params[$k] = array_key_exists($k, $params) ? $params[$k] : null;
        }

        $whereClause = $params['whereClause'];
        if (empty($whereClause)) {
            $whereClause = array();
        }

        if (!$deleted) {
            $whereClause = $whereClause + array('deleted' => 0);
        }

        if (empty($params['joins'])) {
            $params['joins'] = array();
        }
        if (empty($params['leftJoins'])) {
            $params['leftJoins'] = array();
        }
        if (empty($params['customJoin'])) {
            $params['customJoin'] = '';
        }

        $wherePart = $this->getWhere($entity, $whereClause, 'AND', $params);

        $havingClause = $params['havingClause'];
        $havingPart = '';
        if (!empty($havingClause)) {
            $havingPart = $this->getWhere($entity, $havingClause, 'AND', $params);
        }

        if (empty($params['aggregation'])) {
            $selectPart = $this->getSelect($entity, $params['select'], $params['distinct'], $params['skipTextColumns'], $params['maxTextColumnsLength']);
            $orderPart = $this->getOrderPart($entity, $params['orderBy'], $params['order']);

            if (!empty($params['additionalColumns']) && is_array($params['additionalColumns']) && !empty($params['relationName'])) {
                foreach ($params['additionalColumns'] as $column => $field) {
                    $relTableName = $this->toDb($this->sanitize($params['relationName']));
                    $relColumnName = $this->toDb($this->sanitize($column));
                    $selectPart[] = "{$this->getRelationAlias($entity, $params['relationName'])}.{$field} AS {$relColumnName}";
                    if ($params['orderBy'] === $field) {
                        $orderPart = "ORDER BY `$relTableName`.$relColumnName " . $this->prepareOrderParameter($params['order']);
                    }
                }
            }

            if (!empty($params['additionalSelectColumns']) && is_array($params['additionalSelectColumns'])) {
                foreach ($params['additionalSelectColumns'] as $column => $field) {
                    $selectPart[] = "$column AS $field";
                }
            }

        } else {
            $aggDist = false;
            if ($params['distinct'] && $params['aggregation'] == 'COUNT') {
                $aggDist = true;
            }
            $selectPart = [$this->getAggregationSelect($entity, $params['aggregation'], $params['aggregationBy'], $aggDist)];
        }

        $joinsPart = $this->getBelongsToJoins($entity, $params['select'], array_merge($params['joins'], $params['leftJoins']));

        if (!empty($params['customWhere'])) {
            if (!empty($wherePart)) {
                $wherePart .= ' ';
            }
            $wherePart .= $params['customWhere'];
        }

        if (!empty($params['customHaving'])) {
            if (!empty($havingPart)) {
                $havingPart .= ' ';
            }
            $havingPart .= $params['customHaving'];
        }

        if (!empty($params['joins']) && is_array($params['joins'])) {
            // TODO array unique
            $joinsRelated = $this->getJoins($entity, $params['joins'], false, $params['joinConditions']);

            print_r('joins');
            die();
//            if (!empty($joinsRelated)) {
//                if (!empty($joinsPart)) {
//                    $joinsPart .= ' ';
//                }
//                $joinsPart .= $joinsRelated;
//            }
        }

        if (!empty($params['leftJoins']) && is_array($params['leftJoins'])) {
            // TODO array unique
            $joinsRelated = $this->getJoins($entity, $params['leftJoins'], true, $params['joinConditions']);

            print_r('leftJoins');
            die();

//            if (!empty($joinsRelated)) {
//                if (!empty($joinsPart)) {
//                    $joinsPart .= ' ';
//                }
//                $joinsPart .= $joinsRelated;
//            }
        }

        if (!empty($params['customJoin'])) {
            print_r('customJoin');
            die();

//            if (!empty($joinsPart)) {
//                $joinsPart .= ' ';
//            }
//            $joinsPart .= $params['customJoin'];
        }

        $groupByPart = null;
        if (!empty($params['groupBy']) && is_array($params['groupBy'])) {
            $arr = array();
            foreach ($params['groupBy'] as $field) {
                $arr[] = $this->convertComplexExpression($entity, $field);
            }
            $groupByPart = implode(', ', $arr);
        }

        $result = [
            'table'       => [
                'tableName'  => $this->toDb($entity->getEntityType()),
                'tableAlias' => self::TABLE_ALIAS
            ],
            'select'      => $selectPart,
            'joins'       => $joinsPart,
            'where'       => $wherePart,
            'order'       => null,
            'offset'      => null,
            'limit'       => null,
            'distinct'    => false,
            'aggregation' => false,
            'groupBy'     => $groupByPart,
            'having'      => $havingPart,
            'parameters'  => $this->parameters,
        ];

        if (empty($params['aggregation'])) {
            $result['order'] = $orderPart;
            $result['offset'] = $params['offset'];
            $result['limit'] = $params['limit'];
            $result['distinct'] = $params['distinct'];
        } else {
            $result['aggregation'] = $params['aggregation'];
            if ($params['aggregation'] === 'COUNT' && $groupByPart && $havingPart) {
                print_r('Stop here! Do something...');
                die();
//                $sql = "SELECT COUNT(*) AS `AggregateValue` FROM ({$sql}) AS `countAlias`";
            }
        }

        return $result;
    }

    protected function getFunctionPart($function, $part, $entityType, $distinct = false)
    {
        if (!in_array($function, $this->functionList)) {
            throw new \Exception("Not allowed function '" . $function . "'.");
        }
        switch ($function) {
            case 'MONTH':
                return "DATE_FORMAT({$part}, '%Y-%m')";
            case 'DAY':
                return "DATE_FORMAT({$part}, '%Y-%m-%d')";
            case 'WEEK':
            case 'WEEK_0':
                return "CONCAT(YEAR({$part}), '/', WEEK({$part}, 0))";
            case 'WEEK_1':
                return "CONCAT(YEAR({$part}), '/', WEEK({$part}, 5))";
            case 'MONTH_NUMBER':
                $function = 'MONTH';
                break;
            case 'DATE_NUMBER':
                $function = 'DAYOFMONTH';
                break;
            case 'YEAR_NUMBER':
                $function = 'YEAR';
                break;
            case 'WEEK_NUMBER':
                $function = 'WEEK';
                break;
            case 'WEEK_NUMBER_0':
                return "WEEK({$part}, 0)";
            case 'WEEK_NUMBER_1':
                return "WEEK({$part}, 5)";
            case 'HOUR_NUMBER':
                $function = 'HOUR';
                break;
            case 'MINUTE_NUMBER':
                $function = 'MINUTE';
                break;
            case 'DAYOFWEEK_NUMBER':
                $function = 'DAYOFWEEK';
                break;
        }
        if ($distinct) {
            $idPart = $this->toDb($entityType) . ".id";
            switch ($function) {
                case 'SUM':
                case 'COUNT':
                    return $function . "({$part}) * COUNT(DISTINCT {$idPart}) / COUNT({$idPart})";
            }
        }
        return $function . '(' . $part . ')';
    }

    protected function convertMatchExpression($entity, $expression)
    {
        $delimiterPosition = strpos($expression, ':');
        if ($delimiterPosition === false) {
            throw new \Exception("Bad MATCH usage.");
        }

        $function = substr($expression, 0, $delimiterPosition);
        $rest = substr($expression, $delimiterPosition + 1);

        if (empty($rest)) {
            throw new \Exception("Empty MATCH parameters.");
        }

        $delimiterPosition = strpos($rest, ':');
        if ($delimiterPosition === false) {
            throw new \Exception("Bad MATCH usage.");
        }

        $columns = substr($rest, 0, $delimiterPosition);
        $query = mb_substr($rest, $delimiterPosition + 1);

        $columnList = explode(',', $columns);

        $tableName = $this->toDb($entity->getEntityType());

        foreach ($columnList as $i => $column) {
            $columnList[$i] = $tableName . '.' . $this->sanitize($column);
        }

        $query = $this->quote($query);

        if (!in_array($function, $this->matchFunctionList)) {
            return;
        }
        $modePart = ' ' . $this->matchFunctionMap[$function];

        $result = "MATCH (" . implode(',', $columnList) . ") AGAINST (" . $query . "" . $modePart . ")";

        return $result;
    }

    protected function convertComplexExpression($entity, $field, $distinct = false)
    {
        echo '<pre>';
        print_r('convertComplexExpression');
        die();

        $function = null;
        $relName = null;

        $entityType = $entity->getEntityType();

        if (strpos($field, ':')) {
            $dilimeterPosition = strpos($field, ':');
            $function = substr($field, 0, $dilimeterPosition);

            if (in_array($function, $this->matchFunctionList)) {
                return $this->convertMatchExpression($entity, $field);
            }

            $field = substr($field, $dilimeterPosition + 1);
        }
        if (!empty($function)) {
            $function = preg_replace('/[^A-Za-z0-9_]+/', '', $function);
        }

        if (strpos($field, '.')) {
            list($relName, $field) = explode('.', $field);
        }

        if (!empty($relName)) {
            $relName = preg_replace('/[^A-Za-z0-9_]+/', '', $relName);
        }
        if (!empty($field)) {
            $field = preg_replace('/[^A-Za-z0-9_]+/', '', $field);
        }

        $part = $this->toDb($field);
        if ($relName) {
            $part = $relName . '.' . $part;
        } else {
            if (!empty($entity->fields[$field]['select'])) {
                $part = $entity->fields[$field]['select'];
            } else {
                $part = $this->toDb($entityType) . '.' . $part;
            }
        }
        if ($function) {
            $part = $this->getFunctionPart(strtoupper($function), $part, $entityType, $distinct);
        }
        return $part;
    }

    protected function getSelect(IEntity $entity, $fields = null, $distinct = false, $skipTextColumns = false, $maxTextColumnsLength = null)
    {
        $select = "";
        $arr = array();
        $specifiedList = is_array($fields) ? true : false;

        if (empty($fields)) {
            $attributeList = array_keys($entity->fields);
        } else {
            $attributeList = $fields;
            foreach ($attributeList as $i => $attribute) {
                if (!is_array($attribute)) {
                    $attributeList[$i] = $this->sanitizeAlias($attribute);
                }
            }
        }

        foreach ($attributeList as $attribute) {
            $attributeType = null;
            if (is_string($attribute)) {
                $attributeType = $entity->getAttributeType($attribute);
            }
            if ($skipTextColumns) {
                if ($attributeType === $entity::TEXT) {
                    continue;
                }
            }

            if (is_array($attribute) && count($attribute) == 2) {
                if (stripos($attribute[0], 'VALUE:') === 0) {
                    $part = substr($attribute[0], 6);
                    if ($part !== false) {
                        $part = $this->quote($part);
                    } else {
                        $part = $this->quote('');
                    }
                } else {
                    if (!array_key_exists($attribute[0], $entity->fields)) {
                        $part = $this->convertComplexExpression($entity, $attribute[0], $distinct);
                    } else {
                        $fieldDefs = $entity->fields[$attribute[0]];
                        if (!empty($fieldDefs['select'])) {
                            $part = $fieldDefs['select'];
                        } else {
                            if (!empty($fieldDefs['notStorable']) || !empty($fieldDefs['noSelect'])) {
                                continue;
                            }
                            $part = $this->getFieldPath($entity, $attribute[0]);
                        }
                    }
                }

                $arr[] = "{$part} AS {$this->sanitizeAlias($attribute[1])}";
                continue;
            }

            if (array_key_exists($attribute, $entity->fields)) {
                $fieldDefs = $entity->fields[$attribute];
            } else {
                $part = $this->convertComplexExpression($entity, $attribute, $distinct);
                $arr[] = "{$part} AS {$attribute}";
                continue;
            }

            if (!empty($fieldDefs['select'])) {
                $fieldPath = $fieldDefs['select'];
            } else {
                if (!empty($fieldDefs['notStorable'])) {
                    continue;
                }
                if ($attributeType === null) {
                    continue;
                }
                $fieldPath = $this->getFieldPath($entity, $attribute);
            }

            $arr[] = "{$fieldPath} AS {$attribute}";
        }

        return $arr;
    }

    protected function getBelongsToJoin(IEntity $entity, $relationName, $r = null, $alias = null): ?array
    {
        if (empty($r)) {
            $r = $entity->relations[$relationName];
        }

        $keySet = $this->getKeys($entity, $relationName);
        $key = $keySet['key'];
        $foreignKey = $keySet['foreignKey'];

        if (!$alias) {
            $alias = $this->getRelationAlias($entity, $relationName);
        }

        if ($alias) {
            return [
                'fromAlias' => self::TABLE_ALIAS,
                'table'     => $this->connection->quoteIdentifier($this->toDb($r['entity'])),
                'alias'     => $alias,
                'condition' => self::TABLE_ALIAS . "." . $this->toDb($key) . " = " . $alias . "." . $this->toDb($foreignKey)
            ];
        }

        return null;
    }

    protected function getBelongsToJoins(IEntity $entity, $select = null, $skipList = array())
    {
        $joinsArr = array();

        $relationsToJoin = array();
        if (is_array($select)) {
            foreach ($select as $item) {
                $field = $item;
                if (is_array($item)) {
                    if (count($field) == 0) {
                        continue;
                    }
                    $field = $item[0];
                }
                if ($entity->getAttributeType($field) == 'foreign' && $entity->getAttributeParam($field, 'relation')) {
                    $relationsToJoin[] = $entity->getAttributeParam($field, 'relation');
                }
            }
        }

        foreach ($entity->relations as $relationName => $r) {
            if ($r['type'] == IEntity::BELONGS_TO) {
                if (!empty($r['noJoin'])) {
                    continue;
                }
                if (in_array($relationName, $skipList)) {
                    continue;
                }

                if (!empty($select)) {
                    if (!in_array($relationName, $relationsToJoin)) {
                        continue;
                    }
                }

                $join = $this->getBelongsToJoin($entity, $relationName, $r);
                if ($join) {
                    $joinsArr[] = array_merge($join, ['type' => 'left']);
                }
            }
        }

        return $joinsArr;
    }

    protected function getOrderPart(IEntity $entity, $orderBy = null, $order = null)
    {
        if (!is_null($orderBy)) {
            if (is_array($orderBy)) {
                $arr = [];
                foreach ($orderBy as $item) {
                    if (is_array($item)) {
                        $orderByInternal = $item[0];
                        $orderInternal = null;
                        if (!empty($item[1])) {
                            $orderInternal = $item[1];
                        }
                        $arr[] = $this->getOrderPart($entity, $orderByInternal, $orderInternal);
                    }
                }
                return $arr;
            }

            if (strpos($orderBy, 'LIST:') === 0) {
                list($l, $field, $list) = explode(':', $orderBy);
                $fieldPath = $this->getFieldPathForOrderBy($entity, $field);
                $listQuoted = [];
                $list = array_reverse(explode(',', $list));
                foreach ($list as $i => $listItem) {
                    $listItem = str_replace('_COMMA_', ',', $listItem);
                    $listQuoted[] = $this->quote($listItem);
                }
                $part = "FIELD(" . $fieldPath . ", " . implode(", ", $listQuoted) . ") DESC";
                return $part;
            }

            $order = $this->prepareOrderParameter($order);

            if (is_integer($orderBy)) {
                return "{$orderBy} " . $order;
            }

            if (!empty($entity->fields[$orderBy]['isLinkEntity'])) {
                $orderBy = $orderBy . 'Id';
            }

            if (!empty($entity->fields[$orderBy])) {
                $fieldDefs = $entity->fields[$orderBy];
            }
            if (!empty($fieldDefs) && !empty($fieldDefs['orderBy'])) {
                $orderPart = str_replace('{direction}', $order, $fieldDefs['orderBy']);
                return "{$orderPart}";
            } else {
                $fieldPath = $this->getFieldPathForOrderBy($entity, $orderBy);
                return !empty($fieldPath) && is_string($fieldPath) ? "$fieldPath $order" : false;
            }
        }
    }

    protected function prepareOrderParameter($order): string
    {
        if (!is_null($order)) {
            if (is_bool($order)) {
                $order = $order ? 'DESC' : 'ASC';
            }
            $order = strtoupper($order);
            if (!in_array($order, ['ASC', 'DESC'])) {
                $order = 'ASC';
            }
        } else {
            $order = 'ASC';
        }

        return $order;
    }

    protected function getFieldPathForOrderBy($entity, $orderBy)
    {
        if (strpos($orderBy, '.') !== false) {
            list($alias, $field) = explode('.', $orderBy);
            $fieldPath = $this->sanitize($alias) . '.' . $this->toDb($this->sanitize($field));
        } else {
            $fieldPath = $this->getFieldPath($entity, $orderBy);
        }
        return $fieldPath;
    }

    protected function getAggregationSelect(IEntity $entity, $aggregation, $aggregationBy, $distinct = false)
    {
        if (!isset($entity->fields[$aggregationBy])) {
            return false;
        }

        $aggregation = strtoupper($aggregation);

        $distinctPart = '';
        if ($distinct) {
            $distinctPart = 'DISTINCT ';
        }

        $selectPart = "{$aggregation}({$distinctPart}" . self::TABLE_ALIAS . "." . $this->toDb($this->sanitize($aggregationBy)) . ") AS AggregateValue";

        return $selectPart;
    }

    public function quote($value)
    {
        if (is_null($value)) {
            return 'NULL';
        } else {
            if (is_bool($value)) {
                return $value ? '1' : '0';
            } else {
                return $value;
            }
        }
    }

    public function toDb($field)
    {
        if (array_key_exists($field, $this->fieldsMapCache)) {
            return $this->fieldsMapCache[$field];

        } else {
            $field = lcfirst($field);
            $dbField = preg_replace_callback('/([A-Z])/', array($this, 'toDbConversion'), $field);

            $this->fieldsMapCache[$field] = $dbField;
            return $dbField;
        }
    }

    protected function toDbConversion($matches)
    {
        return "_" . strtolower($matches[1]);
    }

    protected function getRelationAlias(IEntity $entity, $relationName)
    {
        if (!isset($this->aliasesCache[$entity->getEntityType()])) {
            $this->aliasesCache[$entity->getEntityType()] = [];
            $occurrenceHash = [];
            foreach ($entity->relations as $name => $r) {
                if ($r['type'] == IEntity::BELONGS_TO) {
                    if (!array_key_exists($name, $this->aliasesCache[$entity->getEntityType()])) {
                        if (array_key_exists($name, $occurrenceHash)) {
                            $occurrenceHash[$name]++;
                        } else {
                            $occurrenceHash[$name] = 0;
                        }
                        $suffix = '_a';
                        if ($occurrenceHash[$name] > 0) {
                            $suffix .= '_' . $occurrenceHash[$name];
                        }
                        $this->aliasesCache[$entity->getEntityType()][$name] = $name . $suffix;
                    }
                }
            }
        }

        if (!isset($this->relationAliases[$entity->getEntityType()][$relationName])) {
            $this->relationAliases[$entity->getEntityType()][$relationName] = $this->toDb(self::sanitize($relationName)) . '_aa';
        }

        return $this->relationAliases[$entity->getEntityType()][$relationName];
    }

    protected function getFieldPath(IEntity $entity, $field)
    {
        if (isset($entity->fields[$field])) {
            $f = $entity->fields[$field];

            if (isset($f['source'])) {
                if ($f['source'] != 'db') {
                    return false;
                }
            }

            if (!empty($f['notStorable'])) {
                return false;
            }

            $fieldPath = '';

            switch ($f['type']) {
                case 'foreign':
                    if (isset($f['relation'])) {
                        $relationName = $f['relation'];
                        $foreign = $f['foreign'];
                        if (is_array($foreign)) {
                            foreach ($foreign as $i => $value) {
                                if ($value == ' ') {
                                    $foreign[$i] = '\' \'';
                                } else {
                                    $foreign[$i] = $this->getRelationAlias($entity, $relationName) . '.' . $this->toDb($value);
                                }
                            }
                            $fieldPath = 'TRIM(CONCAT(' . implode(', ', $foreign) . '))';
                        } else {
                            $fieldPath = $this->getRelationAlias($entity, $relationName) . '.' . $this->toDb($foreign);
                        }
                    }
                    break;
                default:
                    $fieldPath = self::TABLE_ALIAS . '.' . $this->toDb($this->sanitize($field));
            }

            return $fieldPath;
        }

        return false;
    }

    public function getWhere(IEntity $entity, $whereClause, $sqlOp = 'AND', &$params = array(), $level = 0)
    {
        $whereParts = array();

        if (!is_array($whereClause)) {
            $whereClause = array();
        }

        foreach ($whereClause as $field => $value) {

            if (is_int($field)) {
                if (is_string($value)) {
                    if (strpos($value, 'MATCH_') === 0) {
                        $rightPart = $this->convertMatchExpression($entity, $value);
                        $whereParts[] = $rightPart;
                        continue;
                    }
                }
                $field = 'AND';
            }

            if ($field === 'NOT') {
                if ($level > 1) {
                    break;
                }

                $field = 'id!=s';
                $value = array(
                    'selectParams' => array(
                        'select'      => ['id'],
                        'whereClause' => $value
                    )
                );
                if (!empty($params['joins'])) {
                    $value['selectParams']['joins'] = $params['joins'];
                }
                if (!empty($params['leftJoins'])) {
                    $value['selectParams']['leftJoins'] = $params['leftJoins'];
                }
                if (!empty($params['customJoin'])) {
                    $value['selectParams']['customJoin'] = $params['customJoin'];
                }
            }

            if (!in_array($field, self::$sqlOperators)) {
                $isComplex = false;

                $operator = '=';
                $operatorOrm = '=';

                $leftPart = null;

                $isNotValue = false;
                if (substr($field, -1) === ':') {
                    $field = substr($field, 0, strlen($field) - 1);
                    $isNotValue = true;
                }

                if (!preg_match('/^[a-z0-9]+$/i', $field)) {
                    foreach (self::$comparisonOperators as $op => $opDb) {
                        if (strpos($field, $op) !== false) {
                            $field = trim(str_replace($op, '', $field));
                            $operatorOrm = $op;
                            $operator = $opDb;
                            break;
                        }
                    }
                }

                if (strpos($field, '.') !== false || strpos($field, ':') !== false) {
                    $leftPart = $this->convertComplexExpression($entity, $field);
                    $isComplex = true;
                }


                if (empty($isComplex)) {

                    if (!isset($entity->fields[$field])) {
                        $whereParts[] = '0';
                        continue;
                    }

                    $fieldDefs = $entity->fields[$field];

                    $operatorModified = $operator;

                    $attributeType = null;
                    if (!empty($fieldDefs['type'])) {
                        $attributeType = $fieldDefs['type'];
                    }

                    if (
                        is_bool($value)
                        && in_array($operator, ['=', '<>'])
                        && $attributeType == IEntity::BOOL
                    ) {
                        if ($value) {
                            if ($operator === '=') {
                                $operatorModified = '= TRUE';
                            } else {
                                $operatorModified = '= FALSE';
                            }
                        } else {
                            if ($operator === '=') {
                                $operatorModified = '= FALSE';
                            } else {
                                $operatorModified = '= TRUE';
                            }
                        }
                    } else {
                        if (is_array($value)) {
                            if ($operator == '=') {
                                $operatorModified = 'IN';
                            } else {
                                if ($operator == '<>') {
                                    $operatorModified = 'NOT IN';
                                }
                            }
                        } else {
                            if (is_null($value)) {
                                if ($operator == '=') {
                                    $operatorModified = 'IS NULL';
                                } else {
                                    if ($operator == '<>') {
                                        $operatorModified = 'IS NOT NULL';
                                    }
                                }
                            }
                        }
                    }

                    if (!empty($fieldDefs['where']) && !empty($fieldDefs['where'][$operatorModified])) {
                        $whereSqlPart = '';
                        if (is_string($fieldDefs['where'][$operatorModified])) {
                            $whereSqlPart = $fieldDefs['where'][$operatorModified];
                        } else {
                            if (!empty($fieldDefs['where'][$operatorModified]['sql'])) {
                                $whereSqlPart = $fieldDefs['where'][$operatorModified]['sql'];
                            }
                        }
                        if (!empty($fieldDefs['where'][$operatorModified]['leftJoins'])) {
                            foreach ($fieldDefs['where'][$operatorModified]['leftJoins'] as $j) {
                                $jAlias = $this->obtainJoinAlias($j);
                                foreach ($params['leftJoins'] as $jE) {
                                    $jEAlias = $this->obtainJoinAlias($jE);
                                    if ($jEAlias === $jAlias) {
                                        continue 2;
                                    }
                                }
                                $params['leftJoins'][] = $j;
                            }
                        }
                        if (!empty($fieldDefs['where'][$operatorModified]['joins'])) {
                            foreach ($fieldDefs['where'][$operatorModified]['joins'] as $j) {
                                $jAlias = $this->obtainJoinAlias($j);
                                foreach ($params['joins'] as $jE) {
                                    $jEAlias = $this->obtainJoinAlias($jE);
                                    if ($jEAlias === $jAlias) {
                                        continue 2;
                                    }
                                }
                                $params['joins'][] = $j;
                            }
                        }
                        if (!empty($fieldDefs['where'][$operatorModified]['customJoin'])) {
                            $params['customJoin'] .= ' ' . $fieldDefs['where'][$operatorModified]['customJoin'];
                        }
                        if (!empty($fieldDefs['where'][$operatorModified]['distinct'])) {
                            $params['distinct'] = true;
                        }
                        $whereParts[] = str_replace('{value}', $this->stringifyValue($value), $whereSqlPart);
                    } else {
                        if ($fieldDefs['type'] == IEntity::FOREIGN) {
                            $leftPart = '';
                            if (isset($fieldDefs['relation'])) {
                                $relationName = $fieldDefs['relation'];
                                if (isset($entity->relations[$relationName])) {

                                    $alias = $this->getRelationAlias($entity, $relationName);
                                    if ($alias) {
                                        if (!is_array($fieldDefs['foreign'])) {
                                            $leftPart = $alias . '.' . $this->toDb($fieldDefs['foreign']);
                                        } else {
                                            $leftPart = $this->getFieldPath($entity, $field);
                                        }
                                    }
                                }
                            }
                        } else {
                            $leftPart = self::TABLE_ALIAS . '.' . $this->toDb($this->sanitize($field));
                        }
                    }
                }
                if (!empty($leftPart)) {

                    if ($operatorOrm === '=s' || $operatorOrm === '!=s') {
                        if (!is_array($value)) {
                            continue;
                        }
                        if (!empty($value['entityType'])) {
                            $subQueryEntityType = $value['entityType'];
                        } else {
                            $subQueryEntityType = $entity->getEntityType();
                        }
                        $subQuerySelectParams = array();
                        if (!empty($value['selectParams'])) {
                            $subQuerySelectParams = $value['selectParams'];
                        }
                        $withDeleted = false;
                        if (!empty($value['withDeleted'])) {
                            $withDeleted = true;
                        }

                        print_r('createSelectQuery subwhere');
                        die();
//                        $whereParts[] = $leftPart . " " . $operator . " (" . $this->createSelectQuery($subQueryEntityType, $subQuerySelectParams, $withDeleted) . ")";
                    } else {
                        if (!is_array($value)) {
                            if (!is_null($value)) {
                                if ($isNotValue) {
                                    $whereParts[] = $leftPart . " " . $operator . " " . $this->convertComplexExpression($entity, $value);
                                } else {
                                    $whereParts[] = "$leftPart $operator :{$field}_w1";
                                    $this->parameters["{$field}_w1"] = $value;
                                }
                            } else {
                                if ($operator == '=') {
                                    $whereParts[] = $leftPart . " IS NULL";
                                } else {
                                    if ($operator == '<>') {
                                        $whereParts[] = $leftPart . " IS NOT NULL";
                                    }
                                }
                            }
                        } else {
                            $oppose = '';
                            $emptyValue = '0';
                            if ($operator == '<>') {
                                $oppose = 'NOT ';
                                $emptyValue = '1';
                            }
                            if (!empty($value)) {
                                $whereParts[] = $leftPart . " {$oppose}IN " . "(:{$field}_w2)";
                                $this->parameters["{$field}_w2"] = $value;
                            } else {
                                $whereParts[] = $emptyValue;
                            }
                        }
                    }
                }
            } else {
                $internalPart = $this->getWhere($entity, $value, $field, $params, $level + 1);
                if ($internalPart || $internalPart === '0') {
                    $whereParts[] = "(" . $internalPart . ")";
                }
            }
        }
        return implode(" " . $sqlOp . " ", $whereParts);
    }

    public function obtainJoinAlias($j)
    {
        if (is_array($j)) {
            if (count($j)) {
                $joinAlias = $j[1];
            } else {
                $joinAlias = $j[0];
            }
        } else {
            $joinAlias = $j;
        }
        return $joinAlias;
    }

    public function stringifyValue($value)
    {
        if (is_array($value)) {
            $arr = [];
            foreach ($value as $v) {
                $arr[] = $this->quote($v);
            }
            $stringValue = '(' . implode(', ', $arr) . ')';
        } else {
            $stringValue = $this->quote($value);
        }
        return $stringValue;
    }

    public function sanitize($string)
    {
        return preg_replace('/[^A-Za-z0-9_]+/', '', $string);
    }

    public function sanitizeAlias($string)
    {
        return preg_replace('/[^A-Za-z0-9_:.]+/', '', $string);
    }

    protected function getJoins(IEntity $entity, array $joins, $left = false, $joinConditions = array())
    {
        $joinSqlList = [];
        foreach ($joins as $item) {
            $itemConditions = [];
            if (is_array($item)) {
                $relationName = $item[0];
                if (count($item) > 1) {
                    $alias = $item[1];
                    if (count($item) > 2) {
                        $itemConditions = $item[2];
                    }
                } else {
                    $alias = $relationName;
                }
            } else {
                $relationName = $item;
                $alias = $relationName;
            }
            $conditions = [];
            if (!empty($joinConditions[$alias])) {
                $conditions = $joinConditions[$alias];
            }
            foreach ($itemConditions as $left => $right) {
                $conditions[$left] = $right;
            }
            if ($sql = $this->getJoin($entity, $relationName, $left, $conditions, $alias)) {
                $joinSqlList[] = $sql;
            }
        }
        return implode(' ', $joinSqlList);
    }

    protected function buildJoinConditionStatement($entity, $alias = null, $left, $right)
    {
        $sql = '';

        $operator = '=';

        $isNotValue = false;
        if (substr($left, -1) === ':') {
            $left = substr($left, 0, strlen($left) - 1);
            $isNotValue = true;
        }

        if (!preg_match('/^[a-z0-9]+$/i', $left)) {
            foreach (self::$comparisonOperators as $op => $opDb) {
                if (strpos($left, $op) !== false) {
                    $left = trim(str_replace($op, '', $left));
                    $operator = $opDb;
                    break;
                }
            }
        }

        if (strpos($left, '.') > 0) {
            list($alias, $attribute) = explode('.', $left);
            $alias = $this->sanitize($alias);
            $column = $this->toDb($this->sanitize($attribute));
        } else {
            $column = $this->toDb($this->sanitize($left));
        }
        $sql .= "{$alias}.{$column}";

        if (is_array($right)) {
            $arr = [];
            foreach ($right as $item) {
                $arr[] = $item;
            }
            $operator = "IN";
            if ($operator == '<>') {
                $operator = 'NOT IN';
            }
            if (count($arr)) {
                $sql .= " " . $operator . " (" . implode(', ', $arr) . ")";
            } else {
                if ($operator === 'IN') {
                    $sql .= " IS NULL";
                } else {
                    $sql .= " IS NOT NULL";
                }
            }
            return $sql;

        } else {
            $value = $right;
            if (is_null($value)) {
                if ($operator === '=') {
                    $sql .= " IS NULL";
                } else {
                    if ($operator === '<>') {
                        $sql .= " IS NOT NULL";
                    }
                }
                return $sql;
            }

            if ($isNotValue) {
                $rightPart = $this->convertComplexExpression($entity, $value);
                $sql .= " " . $operator . " " . $rightPart;
                return $sql;
            }

            $sql .= " " . $operator . " " . $value;

            return $sql;
        }
    }

    protected function joinSQL(string $prefix, string $table, string $alias): string
    {
        return $prefix . "JOIN {$this->connection->quoteIdentifier($table)} {$alias} ON";
    }

    protected function getJoin(IEntity $entity, $name, $left = false, $conditions = array(), $alias = null)
    {
        $prefix = ($left) ? 'LEFT ' : '';

        if (!$entity->hasRelation($name)) {
            if (!$alias) {
                $alias = $this->sanitize($name);
            }
            $table = $this->toDb($this->sanitize($name));

            $sql = $this->joinSQL($prefix, $table, $alias);

            if (empty($conditions)) {
                return '';
            }

            $joinSqlList = [];
            foreach ($conditions as $left => $right) {
                $joinSqlList[] = $this->buildJoinConditionStatement($entity, $alias, $left, $right);
            }
            if (count($joinSqlList)) {
                $sql .= " " . implode(" AND ", $joinSqlList);
            }

            return $sql;
        }

        $relationName = $name;

        $relOpt = $entity->relations[$relationName];
        $keySet = $this->getKeys($entity, $relationName);

        if (!$alias) {
            $alias = $relationName;
        }

        $alias = $this->sanitize($alias);

        $type = $relOpt['type'];

        echo '<pre>';
        print_r('123232323');
        die();

        switch ($type) {
            case IEntity::MANY_MANY:
                $key = $keySet['key'];
                $foreignKey = $keySet['foreignKey'];
                $nearKey = $keySet['nearKey'];
                $distantKey = $keySet['distantKey'];

                $relTable = $this->toDb($relOpt['relationName']);
                $midAlias = lcfirst($this->sanitize($relOpt['relationName']));

                $distantTable = $this->toDb($relOpt['entity']);

                $midAlias = $alias . 'Middle';

                $sql = "{$prefix}JOIN `{$relTable}` AS `{$midAlias}` ON {$this->toDb($entity->getEntityType())}." . $this->toDb($key) . " = {$midAlias}." . $this->toDb($nearKey)
                    . " AND "
                    . "{$midAlias}.deleted = " . 0;

                if (!empty($relOpt['conditions']) && is_array($relOpt['conditions'])) {
                    $conditions = array_merge($conditions, $relOpt['conditions']);
                }

                $joinSqlList = [];
                foreach ($conditions as $left => $right) {
                    $joinSqlList[] = $this->buildJoinConditionStatement($entity, $midAlias, $left, $right);
                }
                if (count($joinSqlList)) {
                    $sql .= " AND " . implode(" AND ", $joinSqlList);
                }

                $sql .= " {$prefix}JOIN `{$distantTable}` AS `{$alias}` ON {$alias}." . $this->toDb($foreignKey) . " = {$midAlias}." . $this->toDb($distantKey)
                    . " AND "
                    . "{$alias}.deleted = " . 0 . "";

                return $sql;

            case IEntity::HAS_MANY:
            case IEntity::HAS_ONE:
                $foreignKey = $keySet['foreignKey'];
                $distantTable = $this->toDb($relOpt['entity']);

                $sql = "{$prefix}JOIN `{$distantTable}` AS `{$alias}` ON {$this->toDb($entity->getEntityType())}." . $this->toDb('id') . " = {$alias}." . $this->toDb($foreignKey)
                    . " AND "
                    . "{$alias}.deleted = " . 0 . "";


                $joinSqlList = [];
                foreach ($conditions as $left => $right) {
                    $joinSqlList[] = $this->buildJoinConditionStatement($entity, $alias, $left, $right);
                }
                if (count($joinSqlList)) {
                    $sql .= " AND " . implode(" AND ", $joinSqlList);
                }

                return $sql;

            case IEntity::HAS_CHILDREN:
                $foreignKey = $keySet['foreignKey'];
                $foreignType = $keySet['foreignType'];
                $distantTable = $this->toDb($relOpt['entity']);

                $sql = $this->joinSQL($prefix, $distantTable, $alias) . " " . $this->toDb($entity->getEntityType()) . "." . $this->toDb('id') . " = {$alias}." . $this->toDb(
                        $foreignKey
                    )
                    . " AND "
                    . "{$alias}." . $this->toDb($foreignType) . " = " . $entity->getEntityType()
                    . " AND "
                    . "{$alias}.deleted = " . 0;

                $joinSqlList = [];
                foreach ($conditions as $left => $right) {
                    $joinSqlList[] = $this->buildJoinConditionStatement($entity, $alias, $left, $right);
                }
                if (count($joinSqlList)) {
                    $sql .= " AND " . implode(" AND ", $joinSqlList);
                }

                return $sql;

            case IEntity::BELONGS_TO:
                $sql = $prefix . $this->getBelongsToJoin($entity, $relationName, null, $alias);
                return $sql;
        }

        return false;
    }

    public function getKeys(IEntity $entity, string $relationName): array
    {
        $relOpt = $entity->relations[$relationName];
        $relType = $relOpt['type'];

        switch ($relType) {
            case IEntity::BELONGS_TO:
                $key = $this->toDb($entity->getEntityType()) . 'Id';
                if (isset($relOpt['key'])) {
                    $key = $relOpt['key'];
                }
                $foreignKey = 'id';
                if (isset($relOpt['foreignKey'])) {
                    $foreignKey = $relOpt['foreignKey'];
                }
                return array(
                    'key'        => $key,
                    'foreignKey' => $foreignKey,
                );

            case IEntity::HAS_MANY:
            case IEntity::HAS_ONE:
                $key = 'id';
                if (isset($relOpt['key'])) {
                    $key = $relOpt['key'];
                }
                $foreignKey = $this->toDb($entity->getEntityType()) . 'Id';
                if (isset($relOpt['foreignKey'])) {
                    $foreignKey = $relOpt['foreignKey'];
                }
                return array(
                    'key'        => $key,
                    'foreignKey' => $foreignKey,
                );

            case IEntity::HAS_CHILDREN:
                $key = 'id';
                if (isset($relOpt['key'])) {
                    $key = $relOpt['key'];
                }
                $foreignKey = 'parentId';
                if (isset($relOpt['foreignKey'])) {
                    $foreignKey = $relOpt['foreignKey'];
                }
                $foreignType = 'parentType';
                if (isset($relOpt['foreignType'])) {
                    $foreignType = $relOpt['foreignType'];
                }
                return array(
                    'key'         => $key,
                    'foreignKey'  => $foreignKey,
                    'foreignType' => $foreignType,
                );

            case IEntity::MANY_MANY:
                $key = 'id';
                if (isset($relOpt['key'])) {
                    $key = $relOpt['key'];
                }
                $foreignKey = 'id';
                if (isset($relOpt['foreignKey'])) {
                    $foreignKey = $relOpt['foreignKey'];
                }
                $nearKey = $this->toDb($entity->getEntityType()) . 'Id';
                $distantKey = $this->toDb($relOpt['entity']) . 'Id';
                if (isset($relOpt['midKeys']) && is_array($relOpt['midKeys'])) {
                    $nearKey = $relOpt['midKeys'][0];
                    $distantKey = $relOpt['midKeys'][1];
                }
                return array(
                    'key'        => $key,
                    'foreignKey' => $foreignKey,
                    'nearKey'    => $nearKey,
                    'distantKey' => $distantKey
                );
            case IEntity::BELONGS_TO_PARENT:
                $key = $relationName . 'Id';
                $typeKey = $relationName . 'Type';
                return array(
                    'key'        => $key,
                    'typeKey'    => $typeKey,
                    'foreignKey' => 'id'
                );
        }

        return [];
    }

    protected function getSeed($entityType)
    {
        if (empty($this->seedCache[$entityType])) {
            $this->seedCache[$entityType] = $this->entityFactory->create($entityType);
        }

        return $this->seedCache[$entityType];
    }
}
