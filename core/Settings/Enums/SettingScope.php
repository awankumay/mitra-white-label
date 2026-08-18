<?php

namespace Core\Settings\Enums;

enum SettingScope: string
{
    case User = 'user';
    case Unit = 'unit';
    case Organization = 'organization';
    case System = 'system';
}
