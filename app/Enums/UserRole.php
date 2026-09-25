<?php

namespace App\Enums;

enum UserRole: string
{
    case Administrator = 'administrator';
    case Teacher = 'teacher';
    case Student = 'student';
}
