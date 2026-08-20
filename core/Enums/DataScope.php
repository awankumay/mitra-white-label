<?php

namespace Core\Enums;

enum DataScope: string
{
    case Global = 'global';
    case Organization = 'organization';
    case Unit = 'unit';
}
