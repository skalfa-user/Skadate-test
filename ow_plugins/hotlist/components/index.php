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
 * @package ow_plugins.hotlist.components
 * @since 1.0
 */
class HOTLIST_CMP_Index extends OW_Component
{
    private $settingList;

    public function __construct( array $params = array() )
    {
        parent::__construct();

        $service = HOTLIST_BOL_Service::getInstance();

        $authMsg = '';
        $authorized = OW::getUser()->isAuthorized('hotlist', 'add_to_list');
        $status = BOL_AuthorizationService::getInstance()->getActionStatus('hotlist', 'add_to_list');

        if (!$authorized)
        {
            $status = BOL_AuthorizationService::getInstance()->getActionStatus('hotlist', 'add_to_list');
            $authMsg = json_encode($status['msg']);
        }

        $this->assign('authorized', $authorized);
        $this->assign('authMsg', $authMsg);

        if (empty($params))
        {
            $this->settingList = array(
                'number_of_users'=>8
            );
        }
        else
        {
            $this->settingList = $params;
        }

        OW::getDocument()->addScript(OW::getPluginManager()->getPlugin('hotlist')->getStaticJsUrl() . 'jquery.cycle.js');

        $userList = HOTLIST_BOL_Service::getInstance()->getHotList();

        $info = array();
        foreach ($userList as $id=>$user)
        {
            $userDto = BOL_UserService::getInstance()->findUserById($user->userId);

            if (empty($userDto)) continue;

            $info[$id]['userId'] = $user->userId;
            $avatars = BOL_AvatarService::getInstance()->getDataForUserAvatars(array($user->userId));
            
            $event = new OW_Event('bookmarks.is_mark', array(), $avatars);
            OW::getEventManager()->trigger($event);
            
            if ( $event->getData() )
            {
                $avatars = $event->getData();
            }
            
            $info[$id]['avatarUrl'] = $avatars[$user->userId]['src'];
            $info[$id]['url'] = $avatars[$user->userId]['url'];
            $info[$id]['username'] = BOL_UserService::getInstance()->getUserName($user->userId);
			$info[$id]['displayName'] = empty($avatars[$user->userId]['title']) ? BOL_UserService::getInstance()->getUserName($user->userId) : $avatars[$user->userId]['title'];

			$fields =  $this->getFields($user->userId);
                $info[$id]['sex'] = empty($fields['sex']) ? '' : $fields['sex'];
                $info[$id]['age'] = empty($fields['age']) ? '' : $fields['age'];
                $info[$id]['googlemap_location'] = empty($fields['googlemap_location']) ? '' : $fields['googlemap_location'];

            $info[$id]['avatar'] = $avatars[$user->userId];
            $info[$id]['isMarked'] = !empty($avatars[$user->userId]['isMarked']);

        }

        if (!empty($info))
        {
            $this->assign('userList', $info);
        }
        else
        {
            $this->assign('userList', null);
        }

        $user = $service->findUserById(OW::getUser()->getId());
        $this->assign('userInList', !empty($user));

		$this->assign('number_of_users', $this->settingList['number_of_users']);
        $this->assign('number_of_rows', 1);
        $this->assign('count', count($info));

        $js = "
$(document).ready(function() {
    $('.users_slideshow').cycle({
		fx: 'scrollUp',
		speed: 300,
		timeout: 4500,
		cleartype: false
	});
});";
        if (count($info) > $this->settingList['number_of_users'])
        {
            OW::getDocument()->addOnloadScript($js);
        }

    }

    public function getFields( $userId )
    {
        $fields = array();

        $qs = array();

        $qBdate = BOL_QuestionService::getInstance()->findQuestionByName('birthdate');

        if ( $qBdate->onView )
        {
            $qs[] = 'birthdate';
        }

        $qSex = BOL_QuestionService::getInstance()->findQuestionByName('sex');

        if ( $qSex->onView )
        {
            $qs[] = 'sex';
        }

        $qLocation = BOL_QuestionService::getInstance()->findQuestionByName('googlemap_location');
        if ($qLocation)
        {
            if ( $qLocation->onView )
            {
                $qs[] = 'googlemap_location';
            }
        }

        $questionList = BOL_QuestionService::getInstance()->getQuestionData(array($userId), $qs);

        $question = $questionList[$userId];

        if ( !empty($question['birthdate']) )
        {
            $date = UTIL_DateTime::parseDate($question['birthdate'], UTIL_DateTime::MYSQL_DATETIME_DATE_FORMAT);

            $fields['age'] = UTIL_DateTime::getAge($date['year'], $date['month'], $date['day']);
        }

        $sexValue = '';
        if ( !empty($question['sex']) )
        {
            $sex = $question['sex'];

            for ( $i = 0; $i < 31; $i++ )
            {
                $val = pow(2, $i);
                if ( (int) $sex & $val )
                {
                    $sexValue .= BOL_QuestionService::getInstance()->getQuestionValueLang('sex', $val) . ', ';
                }
            }

            if ( !empty($sexValue) )
            {
                $fields['sex'] = substr($sexValue, 0, -2);
            }
        }

        if (!empty($question['googlemap_location']['address']))
        {
            $fields['googlemap_location'] = $question['googlemap_location']['address'];
        }

        return $fields;
    }


}

