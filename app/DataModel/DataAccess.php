<?php

namespace App\DataModel;

use App\DataModel\DatabaseConnection;
use DateTime;
use App\DataModel\DataAccessAttributes\FieldName;
use App\DataModel\DataAccessAttributes\FieldType;
use App\DataModel\DataAccessAttributes\TableName;
use ReflectionClass;
use ReflectionProperty;

class DataAccess
{
    protected $PRM;

    const ARRAYLIST = "ArrayList";
    const OBJECTLIST = "ObjectList";


    #region Static Functions
    static function SQLGetTotalWhere(FilterParameterCollection $paramArray): string
    {
        $SQL = "select count(*) as [total] ";
        $tableName = self::getTableName();

        $SQL .= "from $tableName t ";        

        $SQL .= self::GetWhereConditions($paramArray);
        return $SQL;
    }

    static function SQLGetListWhere(FilterParameterCollection $paramArray, ?int $RowCount = 0, int $Offset = 0, OrderParameter $OrderBy = null, bool $distinct = false): string
    {
        $SQL = self::SQLGetList($distinct);

        $SQL .= self::GetWhereConditions($paramArray);

        if ($OrderBy) {
            $SQL .= "ORDER BY $OrderBy->FieldName $OrderBy->Order ";
        } else {
            $SQL .= "ORDER BY 1 ASC ";
        }

        $SQL .= "OFFSET $Offset ROWS ";

        if ($RowCount > 0) {
            $SQL .= "FETCH NEXT $RowCount ROWS ONLY  ";
        }

        return $SQL;
    }

    static private function GetWhereConditions(FilterParameterCollection $paramArray): string
    {
        $SQL = "";
        $SQLwhere = " where ";
        foreach ($paramArray as $filterParameter) {
            $prefix = ($filterParameter->TablePrefix) ? $filterParameter->TablePrefix . '.' : null;
            $field = $filterParameter->FieldName;
            $value = $filterParameter->Value;
            $operator = $filterParameter->Operator;
            $condition = $filterParameter->Condition;

            $groupStart = "";
            if ($filterParameter->GroupStart) {
                $groupStart = $filterParameter->GroupStart;
            }

            if ($value === null) {
                $SQL .= $SQLwhere . "$groupStart$prefix$field is " . ($operator == FilterParameter::NOTEQUALS ? "not null " : "null ");
            } else {
                $SQL .= $SQLwhere . "$groupStart$prefix$field $operator ";

                switch ($operator) {
                    case FilterParameter::IN:
                    case FilterParameter::NOTIN:
                        $SQL .= "(" . substr(str_repeat(',?', count($value)), 1) . ") ";
                        break;
                    default:
                        $SQL .= "? ";
                        break;
                }
            }

            if ($filterParameter->GroupEnd) {
                $SQL .= $filterParameter->GroupEnd;
            }

            $SQLwhere = " $condition ";
        }

        return $SQL;
    }

    static function SQLGetList(bool $distinct = false)
    {
        //Get Field Array
        $fields = self::getFieldArray();

        //Get Table Name
        $tableName = self::getTableName();

        $SQL = "select ";
        if ($distinct) {
            $SQL .= "distinct ";
        }

        $sep = "";
        foreach ($fields as $field) {
            $SQL .= $sep . $field->Prefix . "." . $field->FieldName . " " . (($field->Alias <> $field->FieldName) ? $field->Alias : "") . " ";
            $sep = ",";
        }

        $SQL .= "from $tableName t ";        

        return $SQL;
    }   

    static function getFieldArray(): array
    {
        $className = get_called_class();

        //Get public properties
        $reflect = new \ReflectionClass($className);
        $properties = $reflect->getProperties(\ReflectionProperty::IS_PUBLIC);

        $fieldArray = [];
        $prefixCount = 1;

        foreach ($properties as $prop) {

            $attributes = $prop->getAttributes();
            $result = array();

            //Get Property Attributes
            foreach ($attributes as $attribute) {
                $attName = $attribute->getName();
                $attValue = $attribute->newInstance();

                $result[$attName] = $attValue;
            }

            $prefix = "t"; // Default table prefix

            $propName = $prop->getName();

            $fieldNameAttribute = $result[FieldName::class] ?? null;
            $fieldName = (!empty($fieldNameAttribute)) ? $fieldNameAttribute->Value : $prop->getName();
            $propType = $prop->getType()?->getName();

            $SQLType = self::GetSQLDataType($propType);

            // Determine if property is required (typed and not nullable)
            $required = false;
            $typeObj = $prop->getType();
            if ($typeObj && !$typeObj->allowsNull()) {
                $required = true;
            }            

            $fieldArray[$propName] = (object)[
                "Prefix" => $prefix,
                "FieldName" => $fieldName,
                "Attributes" => $result,
                "Alias" => $propName,
                'DataType' => $propType,
                'SQLDataType' => $SQLType,
                'Required' => $required,
                'isKey' => isset($result[FieldType::class]) && $result[FieldType::class]->Value == FieldType::KEY
            ];
        }

        return $fieldArray;
    }

    //Returns the table name
    static function getTableName(): string
    {
        $className = get_called_class();

        $tableName = self::getTableNameAttribute($className);
        if ($tableName) return $tableName;

        //If there is no TableName, check parent class
        $parentClassName = get_parent_class($className);
        $tableName = self::getTableNameAttribute($parentClassName);

        if ($tableName) return $tableName;

        //If there is no TableName Attributes, return the class name, removing any namespace.
        $classNameArray = explode("\\", $className);
        $tableName = end($classNameArray);
        return $tableName;
    }


    private static function getTableNameAttribute($className): ?string
    {
        $reflectClass = new ReflectionClass($className);
        $attributes = $reflectClass->getAttributes();

        foreach ($attributes as $attr) {
            $attrName = $attr->getName();
            if ($attrName == TableName::class) {
                //primary table name;
                $tableName = $attr->getArguments()[0];
                return $tableName;
            }
        }
        return null;
    }

    //Returns a single record by key/identity field
    static function SQLGetWhere(): string
    {
        $fields = self::getFieldArray();
        $SQL = self::SQLGetList();

        $SQLwhere = "where ";
        foreach ($fields as $field) {
            $fieldType = $field->Attributes[FieldType::class] ?? null;
            if (isset($fieldType) && ($fieldType->Value == FieldType::KEY || $fieldType->Value == FieldType::IDENTITY)) {

                $prefix = $field->Prefix;
                $fieldName = $field->FieldName;

                $SQL .= $SQLwhere . "$prefix.$fieldName = ?" . PHP_EOL;
                $SQLwhere = "and ";
            }
        }

        return $SQL;
    }

    //Returns SQL for inserting a record
    static function SQLInsert(): string
    {
        $fields = self::getFieldArray();
        $tableName = self::getTableName();

        $insertFields = [];
        foreach ($fields as $field) {
            //Do not insert into into Identity Field or join Field.
            $fieldType = $field->Attributes[FieldType::class] ?? null;
            if (isset($fieldType) and $fieldType->Value == FieldType::IDENTITY) {
                continue;
            }

            $insertFields[] = $field->FieldName;
        }

        return "insert into $tableName(" . implode(",", $insertFields) . ") values (" . substr(str_repeat("?,", count($insertFields)), 0, -1)  .  ")";
    }

    //Returns SQL for updating a record
    static function SQLUpdate(): string
    {
        $fields = self::getFieldArray();
        $tableName = self::getTableName();

        $updateFields = [];
        $keyFields = [];

        foreach ($fields as $field) {           

            //Do not insert into into Identity Field or join Field.
            $fieldType = $field->Attributes[FieldType::class] ?? null;
            if (isset($fieldType) and ($fieldType->Value == FieldType::IDENTITY || $fieldType->Value == FieldType::KEY)) {
                $keyFields[] = $field->FieldName;
            } else if (!isset($fieldType)) {
                $updateFields[] = $field->FieldName;
            }
        }

        $SQL = "update $tableName set " . implode(" = ?,", $updateFields) . " = ?";

        $SQLwhere = " where";
        foreach ($keyFields as $key) {
            $SQL .= $SQLwhere . " $key = ?";
            $SQLwhere = " and";
        }
        return $SQL;
    }

    //Returns SQL for updating a record    
    static function SQLDelete(): string
    {
        $fields = self::getFieldArray();
        $tableName = self::getTableName();

        $keyFields = [];
        foreach ($fields as $field) {
            $fieldType = $field->Attributes[FieldType::class] ?? null;
            if (isset($fieldType) and ($fieldType->Value == FieldType::IDENTITY || $fieldType->Value == FieldType::KEY)) {
                $keyFields[] = $field->FieldName;
            }
        }

        $SQL = "delete from $tableName";

        $SQLwhere = " where";
        foreach ($keyFields as $key) {
            $SQL .= $SQLwhere . " $key = ?";
            $SQLwhere = " and";
        }
        return $SQL;
    }
    
    static function GetSQLDataType(?string $PHPDataType)
    {
        if (is_null($PHPDataType)) return "varchar(max)";

        switch ($PHPDataType) {
            case "string":
                return "varchar(max)";
            case "int":
                return "int";
            default:
                return "varchar(max)";
        }
    }
    #endregion

    #region Table Data Methods

    static function GetList($ListType = DataAccess::ARRAYLIST): array
    {
        $SQL = self::SQLGetList();
        $PRM = [];
        $resultList = self::ExecSqlQuery($SQL, $PRM, $ListType);
        return $resultList;
    }

    static function GetFirstWhere(FilterParameterCollection $paramArray, OrderParameter $OrderBy = null, $OPT = array()): ?object
    {
        $resultArray = self::GetListWhere($paramArray, DataAccess::OBJECTLIST, 1, $OrderBy, $OPT);
        if (count($resultArray) > 0) {
            return $resultArray[0];
        } else {
            return null;
        }
    }

    static function GetTotalWhere(FilterParameterCollection $paramArray): int
    {
        $SQL = self::SQLGetTotalWhere($paramArray);

        $PRM = $paramArray->getValues();
        $resultList = self::ExecSqlQuery($SQL, $PRM, DataAccess::ARRAYLIST, []);
        return $resultList[0]["total"] ?? 0;
    }

    static function GetListWhere(
        FilterParameterCollection $paramArray,
        $ListType = DataAccess::ARRAYLIST,
        $RowCount = 0,
        OrderParameter $OrderBy = null,
        $OPT = array(),
        bool $distinct = false,
        $Offset = 0
    ): array {
        $SQL = self::SQLGetListWhere($paramArray, $RowCount, $Offset, $OrderBy, $distinct);

        $PRM = $paramArray->getValues();
        $resultList = self::ExecSqlQuery($SQL, $PRM, $ListType, $OPT);
        return $resultList;
    }

    static function Get(array $paramArray): ?object
    {
        $SQL = self::SQLGetWhere();

        $PRM = $paramArray;
        $resultList = self::ExecSqlQuery($SQL, $PRM, DataAccess::OBJECTLIST);
        return $resultList[0] ?? null;
    }

    function Insert()
    {
        $SQL = self::SQLInsert();

        $PRM = [];
        $fieldarray = self::getFieldArray();

        $identityFieldName = null;

        foreach ($fieldarray as $field) {
    
            //Exclude IdentityField from Insert
            $fieldType = $field->Attributes[FieldType::class] ?? null;
            if (is_null($fieldType) || $fieldType->Value != FieldType::IDENTITY) {
                $fieldName = $field->FieldName;
                if (isset($this->$fieldName)) {
                    $PRM[] = $this->$fieldName;
                } else {
                    $PRM[] = null;
                }
            } else if ($fieldType->Value == FieldType::IDENTITY) {
                $identityFieldName = $field->FieldName;
            }
        }

        $lastId = $this->ExecSqlInsert($SQL, $PRM);

        //If Table has Identity field, save 
        if ($identityFieldName && $lastId) {
            $this->$identityFieldName = $lastId;
        }
    }

    function InsertJSON($Elements, $Database, $JSONData, $OPT = array())
    {
        $fieldArray = $Elements::getFieldArray();

        //Do not insert into into Identity Field or join Field.
        $fieldArray = array_filter($fieldArray, function ($a) {
            $fieldType = $a->Attributes[FieldType::class] ?? null;
            return (!isset($fieldType) || $fieldType->Value != FieldType::IDENTITY);
        });

        $fieldArrayStr = implode(',', array_column($fieldArray, "FieldName"));
        $propArrayStr = implode(',', array_column($fieldArray, "Alias"));

        $fieldDataTypeStr = implode(',', array_map(function ($a) {
            return $a->FieldName . ' ' . $a->SQLDataType;
        }, $fieldArray));

        $SQL = "
            DECLARE @json VARCHAR(max) = ?
            SET NOCOUNT ON;
            INSERT INTO $Database (" . $fieldArrayStr . ")
            SELECT " . $propArrayStr . "
            FROM OPENJSON(@json)  
            WITH (" . $fieldDataTypeStr . ")
            ";

        $this->ExecSqlNonQuery($SQL, [$JSONData], $OPT);
    }

    function Update()
    {
        $SQL = self::SQLUpdate();

        $fieldarray = self::getFieldArray();

        $updateFields = [];
        $keyfields = [];

        foreach ($fieldarray as $field) {

            $fieldType = $field->Attributes[FieldType::class] ?? null;
            $fieldName = $field->FieldName;
            if (!isset($this->{$fieldName})) {
                $this->{$fieldName} = null;
            }
            if (!is_null($fieldType) && ($fieldType->Value == FieldType::IDENTITY || $fieldType->Value == FieldType::KEY)) {
                if ($this->$fieldName instanceof DateTime) {
                    $keyfields[] = $this->$fieldName->format('Y-m-d');
                } else {
                    $keyfields[] = $this->$fieldName;
                }
            } else if (!isset($fieldType)) {
                $fieldName = $field->FieldName;
                $updateFields[] = $this->$fieldName;
            }
        }

        $PRM = array_merge($updateFields, $keyfields);

        $this->ExecSqlNonQuery($SQL, $PRM);
    }

    function Delete()
    {
        $SQL = self::SQLDelete();
        $fieldarray = self::getFieldArray();
        $keyfields = [];

        foreach ($fieldarray as $field) {
            $fieldType = $field->Attributes[FieldType::class] ?? null;
            if (!is_null($fieldType) && ($fieldType->Value == FieldType::IDENTITY || $fieldType->Value == FieldType::KEY)) {
                $fieldName = $field->FieldName;
                if ($this->$fieldName instanceof DateTime) {
                    $keyfields[] = $this->$fieldName->format('Y-m-d');
                } else {
                    $keyfields[] = $this->$fieldName;
                }
            }
        }
        $PRM = $keyfields;
        $this->ExecSqlNonQuery($SQL, $PRM);
    }

    static function DeleteWhere(array $filters = [])
    {
        $tableName = self::getTableName();

        // Build dynamic WHERE clause based on provided filters
        $whereClause = self::GetWhereConditions(new FilterParameterCollection($filters));

        // Prepare the SQL Delete statement with WHERE clause
        $SQL = "DELETE FROM $tableName $whereClause";

        // Prepare parameters for binding
        $bindParams = [];
        foreach ($filters as $filter) {
            if ($filter->Value !== null) {
                if (is_array($filter->Value)) {
                    foreach ($filter->Value as $v) {
                        $bindParams[] = $v;
                    }
                } else {
                    $bindParams[] = $filter->Value;
                }
            }
        }

        // Execute the SQL statement
        self::ExecSqlNonQuery($SQL, $bindParams);
    }

    /** WARNING: This deletes all records from table */
    static function DeleteAll(bool $deleteAll = false)
    {
        if ($deleteAll) {
            $tableName = self::getTableName();
            $SQL = "delete from $tableName";

            $PRM = [];
            self::ExecSqlNonQuery($SQL, $PRM);
        }
    }

    #endregion

    #region SQL Execution Methods

    // Execute Sql Query to return data
    static function ExecSqlQuery(string $SQL, array $PRM, string $ListType = DataAccess::ARRAYLIST)
    {
        $dbInstance = DatabaseConnection::getInstance();

        $className = get_called_class();
        
        $qry = $dbInstance->prepare($SQL);
        $qry->execute($PRM); 

         switch ($ListType) {
            case DataAccess::ARRAYLIST:
                $result = $qry->fetchAll(\PDO::FETCH_ASSOC);
                break;
            case DataAccess::OBJECTLIST:
                $result = $qry->fetchAll(\PDO::FETCH_CLASS, $className);
                break;
        }

        return $result;
    }

    static function ExecSqlNonQuery(string $SQL, array $PRM): void
    {
        $dbInstance = DatabaseConnection::getInstance();

        $qry = $dbInstance->prepare($SQL);
        $qry->execute($PRM); 

    }

    static function ExecSqlInsert(string $SQL, array $PRM): ?int
    {
        $dbInstance = DatabaseConnection::getInstance();

        $qry = $dbInstance->prepare($SQL);
        $qry->execute($PRM); 
        
        $id = $dbInstance->lastInsertId();

        return $id;
    }
    #endregion


  

    
}
