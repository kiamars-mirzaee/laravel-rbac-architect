<?php

namespace Kiamars\RbacArchitect\Enums;

enum UserType: string
{
    case SYSTEM = 'system';
    case SITE = 'site';
}