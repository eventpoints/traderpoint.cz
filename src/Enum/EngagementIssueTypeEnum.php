<?php

namespace App\Enum;

enum EngagementIssueTypeEnum: string
{
    case NO_SHOW = 'issue.no_show';
    case CANT_CONTACT = 'issue.cant_contact';
    case CHANGE_AGREEMENT = 'issue.change_agreement';
    case WORK_QUALITY = 'issue.work_quality';
    case PAYMENT_PROBLEM = 'issue.payment_problem';
    case OTHER = 'issue.other';

}
