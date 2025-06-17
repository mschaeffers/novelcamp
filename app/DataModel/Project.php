<?php 

namespace App\DataModel;

use DateTime;

class Project
{
    public string $ProjectId;
    public string $Name;
    public ?string $Description;
    public string $Author;
    public DateTime $CreatedDate;
}
