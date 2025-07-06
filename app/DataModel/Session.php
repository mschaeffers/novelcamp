<?php 

namespace App\DataModel;

use App\DataModel\DataAccess;
use App\DataModel\DataAccessAttributes\FieldType;
use App\DataModel\DataAccessAttributes\TableName;

#[TableName("session")] 
class Session extends DataAccess
{
    #[FieldType(FieldType::IDENTITY)]
    public int $SessionId;
    public int $UserId;
    public string $SessionToken;
    public \DateTime $loginTime;    
}