<?php
declare(strict_types = 1);

namespace GearmanDeamon;

interface ClientInterface {


    const PRIORITY_LOW = 1;
    const PRIORITY_MEDIUM = 2;
    const PRIORITY_HIGH = 3;

    /**
     * @param string $function_name
     * @param array $workload
     * @param string $unique
     * @param int $priority
     * @return bool
     */
    public function add(string $function_name, array $workload, string $unique = NULL, $priority = self::PRIORITY_LOW) : bool;
    
    
}