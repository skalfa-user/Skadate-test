<?php

use Skadate\Traits\RemoteDb;

/**
 * Firebird live context.
 */
class FirebirdLiveContext extends FirebirdContext {
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
        $this->setMinkParameter('base_url', $this->params['live_firebird_url']);
    }
}
