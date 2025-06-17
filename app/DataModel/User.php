<?php 

namespace App\DataModel;

use App\DataModel\DataAccess;
use App\DataModel\DataAccessAttributes\FieldType;
use App\DataModel\DataAccessAttributes\TableName;

#[TableName("user")]
class User extends DataAccess
{
    #[FieldType(FieldType::IDENTITY)]
    public int $UserId;
    public string $Email;
    public string $Username;
    public string $PasswordHash;
    public ?string $FirstName;
    public ?string $LastName;
    public ?string $ProfilePictureUrl;
    public ?string $Bio;
    public ?string $Salt;
}