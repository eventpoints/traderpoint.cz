<?php

namespace App\Enum;

enum TimelinePreferenceEnum: string
{
    case ASAP = 'timeline.preference.asap';
    case WITHIN_WEEK = 'timeline.preference.week';
    case WITHIN_TWO_WEEK = 'timeline.preference.two-week';
    case WITHIN_MONTH = 'timeline.preference.month';
    case FLEXIBLE = 'timeline.preference.flexible';

}
