<?php

/**
 * Copyright (c) 2017, Skalfa LLC
 * All rights reserved.
 * 
 * ATTENTION: This commercial software is intended for exclusive use with SkaDate Dating Software (http://www.skadate.com) and is licensed under SkaDate Exclusive License by Skalfa LLC.
 * 
 * Full text of this license can be found at http://www.skadate.com/sel.pdf
 */

BOL_ComponentAdminService::getInstance()->deleteWidget('HOTLIST_CMP_IndexWidget');
BOL_ComponentAdminService::getInstance()->deleteWidget('HOTLIST_MCMP_Widget');
new BASE_CMP_AuthorizationLimited();