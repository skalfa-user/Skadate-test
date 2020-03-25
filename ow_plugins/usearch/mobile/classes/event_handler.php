<?php

/**
 * Copyright (c) 2016, Skalfa LLC
 * All rights reserved.
 * 
 * ATTENTION: This commercial software is intended for exclusive use with SkaDate Dating Software (http://www.skadate.com)
 * and is licensed under SkaDate Exclusive License by Skalfa LLC.
 * 
 * Full text of this license can be found at http://www.skadate.com/sel.pdf
 */

/**
 * User search event handler class
 *
 * @author Podyachev Evgeny <joker.OW2@gmail.com>
 * @package ow.plugin.usearch.mobile.classes
 * @since 1.5.3
 */
class USEARCH_MCLASS_EventHandler
{
    /**
     * @var USEARCH_CLASS_EventHandler
     */
    private static $classInstance;

    const EVENT_COLLECT_USER_ACTIONS = 'usearch.collect_user_actions';

    /**
     * @return USEARCH_CLASS_EventHandler
     */
    public static function getInstance()
    {
        if ( self::$classInstance === null )
        {
            self::$classInstance = new self();
        }

        return self::$classInstance;
    }

    private function __construct() { 
        
    }
    
    public function init() {
        OW::getEventManager()->bind('class.get_instance', array($this, 'getClassInstance'));
    }
    
    public function getClassInstance(OW_Event $event) {
        $params = $event->getParams();
        
        $className = $params['className'];
        $arguments = $params['arguments'];
        
        if ( $className == 'USEARCH_CMP_SearchResultList' )
        {
            $event->setData(new USEARCH_MCMP_SearchResultList($arguments[0], $arguments[1], (!empty($arguments[2]) ? $arguments[2] : null), (!empty($arguments[3]) ? $arguments[3] : false)));
        }
    }
}