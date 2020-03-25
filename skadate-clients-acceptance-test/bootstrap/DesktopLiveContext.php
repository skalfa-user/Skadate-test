<?php

use Skadate\Traits\RemoteDb;

/**
 * Desktop live context.
 */
class DesktopLiveContext extends DesktopContext
{
    use RemoteDb;

    /**
     * Initializes context.
     *
     * @param string $paramsJson
     */
    public function __construct($paramsJson)
    {
        parent::__construct($paramsJson);

        $this->setSalt($this->skadateConfig['OW_PASSWORD_SALT']);
        $this->setMarioUrl($this->params['mario_url']);
        $this->setDbTablePrefix($this->skadateConfig['OW_DB_PREFIX']);
    }

    /**
     * Set base url
     */
    public function setBaseUrl()
    {
        $this->setMinkParameter('base_url', $this->params['live_desktop_url']);
    }
}
