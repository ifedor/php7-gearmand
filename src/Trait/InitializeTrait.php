<?php
declare(strict_types = 1);

namespace GearmanDeamon;

trait InitializeTrait
{
    /** @var bool  */
    protected $_initialized = false;

    /**
     * @throws \Exception
     */
    public function init()
    {
        if(!method_exists($this, 'addServer') || !method_exists($this, 'setTimeout')) {
            throw new \Exception('Object must have methods: "addServer", "setTimeout"');
        }
        $this->addServer($this->_settings['host'], $this->_settings['port']);
        $this->setTimeout($this->_settings['timeout']);
        $this->_initialized = true;
        return $this;
    }
}
