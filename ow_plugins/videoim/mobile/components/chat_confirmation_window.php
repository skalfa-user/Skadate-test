<?php

/**
 * Copyright (c) 2016, Skalfa LLC
 * All rights reserved.
 *
 * ATTENTION: This commercial software is intended for use with Oxwall Free Community Software http://www.oxwall.com/
 * and is licensed under Oxwall Store Commercial License.
 *
 * Full text of this license can be found at http://developers.oxwall.com/store/oscl
 */

/**
 * Chat confirmation window
 *
 * @author Alex Ermashev <alexermashev@@gmail.com>
 * @package ow_plugins.videoim.mobile.components
 * @since 1.8.1
 */
class VIDEOIM_MCMP_ChatConfirmationWindow extends OW_MobileComponent
{
    /**
     * Service
     *
     * @var VIDEOIM_BOL_VideoImService
     */
    protected $service;

    /**
     * User id
     *
     * @var integer
     */
    protected $userId;

    /**
     * Class constructor
     *
     * @param integer $userId
     */
    public function __construct($userId)
    {
        parent::__construct();

        $this->userId = $userId;
        $this->service = VIDEOIM_BOL_VideoImService::getInstance();
    }

    /**
     * On before render
     *
     * @return void
     */
    public function onBeforeRender()
    {
        parent::onBeforeRender();

        // assign view variables
        $this->assign('closeTime', VIDEOIM_BOL_NotificationDao::NOTIFICATIONS_LIFETIME * 1000);
        $this->assign('senderId', $this->userId);
        $this->assign('senderAvatar', $this->service->getUserAvatar($this->userId, 1));
        $this->assign('senderName', BOL_UserService::getInstance()->getDisplayName($this->userId));
        $this->assign('senderUrl', BOL_UserService::getInstance()->getUserUrl($this->userId));
        $this->assign('isSuperModerator', $this->service->isSuperModerator($this->userId));
    }
}