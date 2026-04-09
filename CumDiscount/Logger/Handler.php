<?php
declare(strict_types=1);

namespace Prostor\CumDiscount\Logger;

use Magento\Framework\Logger\Handler\Base;
use Monolog\Logger;

class Handler extends Base
{
    /**
     * @var int
     */
    protected $loggerType = Logger::WARNING;

    /**
     * @var string
     */
    protected $fileName = '/var/log/prostor_cumdiscount.log';
}
