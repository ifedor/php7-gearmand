<?php
declare(strict_types = 1);

namespace GearmanDeamon;

/**
 * Class Client
 * @package GearmanDeamon
 */
class Client extends \GearmanClient implements ClientInterface {

    use InitializeTrait;

    /** @var array  */
    private $_priority_map = [
        self::PRIORITY_LOW    => 'doLowBackground',
        self::PRIORITY_MEDIUM => 'doBackground',
        self::PRIORITY_HIGH   => 'doHighBackground',
    ];

    /** @var array $_settings */
    protected $_settings = [
        'host' => '127.0.0.1',
        'port' => '4730',
        'timeout' => '60000',
    ];

    /** @var $_last_job_id */
    protected $_last_job_id;

    public function __construct(array $settings)
    {
        if (!extension_loaded('gearman')) {
            throw new ClientRuntimeException('The PECL::gearman extension is required.');
        }

        if (!extension_loaded('pcntl')) {
            throw new ClientRuntimeException('The php pcntl extension is required.');
        }

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

    public function getLastJobId()
    {
        return (empty($this->_last_job_id))? false : $this->_last_job_id;
    }

    public function add(string $function_name, array $workload, string $unique = NULL, $priority = self::PRIORITY_LOW) : bool
    {
        if($this->_initialized === false) {
            $this->init();
        }
        if(!empty($workload)){
            $workload = json_encode($workload);
            if (!isset($this->_priority_map[$priority])) {
                throw new ClientArgumentException('Wrong priority: ' . var_export($priority, true));
            }
            $unique = strval($unique);
            $job_id = $this->{$this->_priority_map[$priority]}($function_name, $workload, $unique);
            $result = ($this->returnCode() === GEARMAN_SUCCESS);
            $this->_last_job_id = $job_id;
        } else {
            throw new ClientArgumentException('Empty workload');
        }
        return  $result;
    }

    public function immediately(string $function_name, array $workload, string $unique = NULL) : string
    {
        if($this->_initialized === false) {
            $this->init();
        }
        if (is_array($workload)) {
            $workload = json_encode($workload);
        }
        return $this->doNormal($function_name, $workload, $unique);
    }

    public function status(string $function_name) : array
    {
        $result = [];
        if($this->_initialized === false) {
            $this->init();
        }
        $stat = $this->jobStatus($function_name);
        if ($stat[0]) {
            $result = [
                'running' => $stat[1],
                'done' => $stat[2],
                'total' => $stat[3],
            ];
        }
        return $result;
    }
}