<?php
#Filename: FilterParameterCollection
namespace App\DataModel;
use ArrayIterator, Exception;

/**
 * Contains the list of **where** parameter for a SQL statement.
 */
class FilterParameterCollection extends ArrayIterator
{
    public function __construct(array $parameters = [])
    {
        foreach ($parameters as $parameter) {
            $this->append($parameter);
        }
    }

    public function append($value): void
    {
        if (!$value instanceof FilterParameter) {
            throw new Exception("Invalid class added to collection");
        }
        parent::append($value);
    }

    public function offsetSet($index, $newval): void
    {
        if (!$newval instanceof FilterParameter) {
            throw new Exception("Invalid class added to collection");
        }
        parent::offsetSet($index, $newval);
    }

    public function getValues(): array
    {
        $valueArray = [];
        foreach ($this as $parameter) {
            if(is_array($parameter->Value)){
                foreach($parameter->Value as $item){
                    $valueArray[] = $item;
                }
            } else {
                //Id Value instance of Date, then format to Y-m-d H:i:s
                if ($parameter->Value instanceof \DateTime) {
                    $valueArray[] = $parameter->Value->format('Y-m-d H:i:s');
                } else {
                    $valueArray[] = $parameter->Value;
                }    
            }
        }
        return $valueArray;
    }
}

/**
 * Sets the **where** parameter for a SQL statement.
 */
class FilterParameter
{
    const EQUALS = "=";
    const NOTEQUALS = "!=";
    const GREATERTHEN = ">=";
    const LESSTHEN = "<=";

    const ISNULL = "is null";
    const ISNOTNULL = "is not null";

    const LIKE = "like";
    const NOTLIKE = "not like";

    const IN = 'in';
    const NOTIN = 'not in';

    const AND = 'and';
    const OR = 'or';

    public $FieldName;
    public $Value;
    public $Operator;
    public $TablePrefix;
    public bool $CoalesceField;
    public $Condition;
    public $GroupStart;
    public $GroupEnd;

    public function __construct(string $fieldName, $value, 
        string $operator = FilterParameter::EQUALS, string $prefix = null, bool $coalesceField = false,
        string $condition = FilterParameter::AND, string $groupStart = null, string $groupEnd = null)
    {
        $this->FieldName = $fieldName;
        $this->Value = $value;
        $this->Operator = $operator;
        $this->TablePrefix = $prefix;
        $this->CoalesceField = $coalesceField;
        $this->Condition = $condition;
        $this->GroupStart = $groupStart;
        $this->GroupEnd = $groupEnd;
    }
}

/**
 * Sets the **order by** parameter for a SQL statement.
 */
class OrderParameter
{
    const DESCENDING = "desc";
    const ASCENDING = "asc";

    public $FieldName;
    public $Order;
    public $TablePrefix;

    public function __construct(string $fieldName, string $order = OrderParameter::ASCENDING, string $prefix = null)
    {
        $this->FieldName = $fieldName;
        $this->Order = $order;
        $this->TablePrefix = $prefix;
    }
}

