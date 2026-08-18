<?php

namespace App\Core\Api\Query;

/**
 * Supported operators for API filter expressions.
 *
 * The enum values are part of the public Pixely API query syntax.
 */
enum FilterOperator: string
{
    case Equal = 'equal';
    case NotEqual = 'notEqual';

    case Less = 'less';
    case LessOrEqual = 'lessOrEqual';

    case Greater = 'greater';
    case GreaterOrEqual = 'greaterOrEqual';

    case In = 'in';
    case NotIn = 'notIn';

    case IsNull = 'isNull';
    case IsNotNull = 'isNotNull';

    case BeginWith = 'beginWith';
    case DoNotBeginWith = 'doNotBeginWith';

    case Contains = 'contains';
    case StrictEqual = 'strictEqual';
    case DoNotContains = 'doNotContains';

    case EndWith = 'endWith';
    case DoNotEndWith = 'doNotEndWith';

    case IsEmpty = 'isEmpty';
    case IsNotEmpty = 'isNotEmpty';

    case Between = 'between';
    case NotBetween = 'notBetween';
}
