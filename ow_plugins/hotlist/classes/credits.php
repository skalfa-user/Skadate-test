<?php

/**
 * Copyright (c) 2017, Skalfa LLC
 * All rights reserved.
 * 
 * ATTENTION: This commercial software is intended for exclusive use with SkaDate Dating Software (http://www.skadate.com) and is licensed under SkaDate Exclusive License by Skalfa LLC.
 * 
 * Full text of this license can be found at http://www.skadate.com/sel.pdf
 */

/**
 * @author Zarif Safiullin <zaph.saph@gmail.com>
 * @package ow_plugins.hotlist.classes
 * @since 1.0
 */
class HOTLIST_CLASS_Credits
{
    private $actions;

    public function __construct()
    {
        $this->actions[] = array('pluginKey' => 'hotlist', 'action' => 'add_to_list', 'amount' => 0);

        $this->authActions['add_to_list'] = 'add_to_list';
    }

    public function getActionCost()
    {
        return $this->actions[0]['amount'];
    }

    public function bindCreditActionsCollect( BASE_CLASS_EventCollector $e )
    {
        foreach ( $this->actions as $action )
        {
            $e->add($action);
        }
    }

    public function triggerCreditActionsAdd()
    {
        $e = new BASE_CLASS_EventCollector('usercredits.action_add');

        foreach ( $this->actions as $action )
        {
            $e->add($action);
        }

        OW::getEventManager()->trigger($e);
    }
}