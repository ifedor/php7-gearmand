<?php
declare(strict_types = 1);

namespace GearmanDeamon;

class Worker extends \GearmanWorker implements WorkerInterface {

    use InitializeTrait;

    /** @var array $_settings */
    protected $_settings = [
        'host'          => '127.0.0.1',
        'port'          => 4730,
        'timeout'       => 60000,
        'stop_timeout'  => 600,
    ];

    protected $_functions = [];

    /** @var callable[] */
    protected $_exception_handlers = [];

    public function __construct(array $settings) {
        parent::__construct();

        $this->validateSettings($settings);
    }

    protected function validateSettings(array $settings) : bool
    {
        if(empty($settings['host'])) {
            throw new ClientArgumentException('"host" can\'t be empty');
        }
        $this->_settings = array_merge($this->_settings, $settings);
        $this->_settings['port'] = intval($this->_settings['port']);
        $this->_settings['timeout'] = intval($this->_settings['timeout']);

        return true;
    }

    public function add_function(string $function_name, $function, $object = NULL, $context = NULL) : bool {
        if (isset($this->_functions[$function_name])) {
            throw new WorkerArgumentException($function_name . ' already added');
        }

        $this->_functions[$function_name] = [$function, $context];

        return true;
    }

    public function start() {
        if($this->_initialized === false) {
            $this->init();
        }
        foreach ($this->_functions as $function_name => $params) {
            $context = $params[1];

            if (!$this->addFunction($function_name, [$this, 'hold'], $context)) {
                $error = $this->getErrno() . ': ' . $this->error();
                throw new WorkerRuntimeException('Add function failed, error #' . $error);
            }
        }
        $start_time = time();
        $timeout = $this->_settings['stop_timeout'];
        while(true) {
            if (!($is_worked = $this->work())) {
                if ($this->getErrno() !== 0) {
                    $error = $this->getErrno() . ': ' . $this->error();
                    throw new WorkerRuntimeException('Work failed, error #' . $error);
                }
            }
            if (time() - $start_time >= $timeout) {
                break;
            }
        }
    }

    public function hold(\GearmanJob $job) {
        list($function, $context) = $this->_functions[$job->functionName()];
        try {
            $result = call_user_func($function, json_decode($job->workload(), TRUE), $context);
            $job->sendComplete(json_encode($result));
        } catch (\Exception $ex) {
            $this->handle_exception($ex);
            $report = [
                'error'      => $ex->getMessage(),
                'error_code' => $ex->getCode(),
            ];
            $job->sendComplete(json_encode($report));
        }
    }

    public function add_exception_handler(callable $handler)
    {
        $this->_exception_handlers[] = $handler;
        end($this->_exception_handlers);
        return key($this->_exception_handlers);
    }

    protected function handle_exception(\Exception $e)
    {
        foreach ($this->_exception_handlers as $handler) {
            if(is_callable($handler)) {
                $handler($e);
            } else {
                throw new WorkerArgumentException('$handler is not callable');
            }
        }
    }
}