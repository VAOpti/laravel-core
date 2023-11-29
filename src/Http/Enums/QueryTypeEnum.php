<?php

namespace VisionAura\LaravelCore\Http\Enums;

enum QueryTypeEnum: string
{
    case WHERE = 'where';
    case OR_WHERE = 'orWhere';

    case WHERE_NOT = 'whereNot';
    case OR_WHERE_NOT = 'orWhereNot';

    case WHERE_IN = 'whereIn';
    case OR_WHERE_IN = 'orWhereIn';
    
    case WHERE_NOT_IN = 'whereNotIn';
    case OR_WHERE_NOT_IN = 'orWhereNotIn';
}
