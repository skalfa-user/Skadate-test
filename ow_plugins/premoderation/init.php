<?php

/**
 * Copyright (c) 2016, Skalfa LLC
 * All rights reserved.
 *
 * ATTENTION: This commercial software is intended for use with Oxwall Free Community Software http://www.oxwall.org/
 * and is licensed under SkaDate Exclusive License by Skalfa LLC.
 *
 * Full text of this license can be found at http://www.skadate.com/sel.pdf
 */

MODERATION_CLASS_EventHandler::getInstance()->init();

OW::getRouter()->addRoute(new OW_Route('moderation.approve', 'moderation/approve/:group', 'MODERATION_CTRL_Moderation', 'approve'));
OW::getRouter()->addRoute(new OW_Route('moderation.approve_index', 'moderation/approve', 'MODERATION_CTRL_Moderation', 'approve'));

OW::getRouter()->addRoute(new OW_Route('moderation.user.approve', 'pending-approval/:userId/:group', 'MODERATION_CTRL_Moderation', 'approve'));
OW::getRouter()->addRoute(new OW_Route('moderation.user.approve_index', 'pending-approval/:userId', 'MODERATION_CTRL_Moderation', 'approve'));

OW::getRouter()->addRoute(new OW_Route('moderation.admin', 'admin/plugins/moderation', 'MODERATION_CTRL_Admin', 'index'));