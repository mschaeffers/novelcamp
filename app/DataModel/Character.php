<?php

namespace App\DataModel;

class Character
{
    public string $CharacterId;
    public string $FirstName;
    public ?string $LastName;
    public ?string $Description;
    public ?string $Bio;
    public ?string $Origin;
    public ?string $Nationality;
    public ?string $Occupation;

    public ?string $Personality;
    public ?string $Likes;
    public ?string $Dislikes;
    public ?string $Quirks;
    public ?string $Fears;
    public ?string $Goals;
    public ?string $Secrets;
    public ?string $Strengths;
    public ?string $Weaknesses;
    public ?string $Skills;
    public ?string $Hobbies;

    public ?string $ImageUrl;
    public ?string $ImageThumbnailUrl;
    
    public ?string $Gender;
    public ?string $Age;

    public ?string $HairColour;
    public ?string $EyeColour;

   
}