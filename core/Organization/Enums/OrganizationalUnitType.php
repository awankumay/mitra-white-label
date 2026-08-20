<?php

namespace Core\Organization\Enums;

enum OrganizationalUnitType: string
{
    case HEAD_OFFICE = 'HEAD_OFFICE';
    case BRANCH = 'BRANCH';
    case SUB_OFFICE = 'SUB_OFFICE';
    case SITE = 'SITE';
}
