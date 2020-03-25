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
 * @package ow.ow_plugins.hotlist.bol
 * @since 1.0
 */
class HOTLIST_BOL_User extends OW_Entity {

    /**
     *
     * @var integer
     */
    public $userId;
    /**
     *
     * @var integer
     */
    public $timestamp;
    /**
     *
     * @var integer
     */
    public $expiration_timestamp;
}