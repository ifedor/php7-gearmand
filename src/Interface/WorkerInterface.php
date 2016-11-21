<?php
declare(strict_types = 1);

namespace GearmanDeamon;

interface WorkerInterface {

    const PRIORITY_LOW = 1;
    const PRIORITY_MEDIUM = 2;
    const PRIORITY_HIGH = 3;

    /**
     * @param string $function_name
     * @param callable $function
     * @param null $context
     * @return bool
     */
    public function add_function(string $function_name, callable $function, $context = NULL) : bool;

    /**
     * @return mixed
     */
    public function start();

    /**
     * @param \GearmanJob $job
     * @return mixed
     */
    public function hold(\GearmanJob $job);

    /**
     * @param callable $handler
     * @return mixed
     */
    public function add_exception_handler(callable $handler);
}