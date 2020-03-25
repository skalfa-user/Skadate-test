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

class PCGALLERY_CMP_Gallery extends OW_Component
{
    const PHOTO_COUNT = 7;
    const PHOTO_CHANGE_INTERVAL = 1000;
    const PHOTO_LIMIT = 200;

    protected $userId;
    protected $uniqId;
    
    /**
     *
     * @var BOL_Avatar
     */
    protected $avatarDto;

    public function __construct( $userId )
    {
        parent::__construct();

        $this->userId = $userId;
        $this->uniqId = uniqid('pcgallery-');

        if ( !PCGALLERY_CLASS_PhotoBridge::getInstance()->isActive() )
        {
            $this->setVisible(false);
        }
        
        $this->avatarDto = BOL_AvatarService::getInstance()->findByUserId($userId);
    }

    private function getUserInfo()
    {
        $permissions = $this->getPemissions();
        $user = array();

        $user['id'] = $this->userId;

        $onlineUser = BOL_UserService::getInstance()->findOnlineUserById($this->userId);
        $user['isOnline'] = $onlineUser !== null;

        $avatar = BOL_AvatarService::getInstance()->getAvatarUrl($this->userId, 2, null, false, !$permissions["viewAvatar"]);  
        
        $user['avatar'] = $avatar ? $avatar : BOL_AvatarService::getInstance()->getDefaultAvatarUrl(2);

        $roles = BOL_AuthorizationService::getInstance()->getRoleListOfUsers(array($this->userId));

        $user['role'] = !empty($roles[$this->userId]) ? $roles[$this->userId] : null;

        $user['displayName'] = BOL_UserService::getInstance()->getDisplayName($this->userId);

        return $user;
    }

    public function getPemissions()
    {
        static $permissions = null;
        
        if ( !empty($permissions) )
        {
            return $permissions;
        }

        $permissions = array(
            'changeAvatar' => false,
            'uploadPhotos' => false,
            'selfMode' => false
        );

        $selfMode = $this->userId == OW::getUser()->getId();
        
        $permissions['selfMode'] = $selfMode;
        $permissions['changeSettings'] = $selfMode;
        $permissions['changeAvatar'] = $selfMode;
        $permissions['uploadPhotos'] = $selfMode;
        $permissions['viewAvatar'] = ($this->avatarDto && $this->avatarDto->status == "active") 
                || $selfMode || OW::getUser()->isAuthorized("base");
        
        $permissions['approveAvatar'] = OW::getUser()->isAuthorized("base");
        
        $permissions['view'] = $selfMode || OW::getUser()->isAuthorized("photo");
        
        if ( !$permissions['view'] )
        {
            $event = new OW_Event('privacy_check_permission', array(
                'action' => "photo_view_album",
                'ownerId' => $this->userId, 
                'viewerId' => OW::getUser()->getId()
            ));

            try 
            {
                OW::getEventManager()->trigger($event);
                $permissions['view'] = true;
            }
            catch ( RedirectException $e )
            {
                // Pass
            }
        }
        
        return $permissions;
    }

    public function getPhotos()
    {
        $source = BOL_PreferenceService::getInstance()->getPreferenceValue("pcgallery_source", $this->userId);
        $album = BOL_PreferenceService::getInstance()->getPreferenceValue("pcgallery_album", $this->userId);
        
        if ( $source == "album" )
        {
            $photos = PCGALLERY_CLASS_PhotoBridge::getInstance()->getAlbumPhotos($this->userId, $album, array(0, self::PHOTO_LIMIT));
        }
        else
        {
            $photos = PCGALLERY_CLASS_PhotoBridge::getInstance()->getPhotos($this->userId, array(0, self::PHOTO_LIMIT));
        }

        if ( count($photos) < self::PHOTO_COUNT )
        {
            return array();
        }

        return $photos;
    }
    
    public function initEmptyGallery()
    {
        $source = BOL_PreferenceService::getInstance()->getPreferenceValue("pcgallery_source", $this->userId);
        
        if ( $source == "all" )
        {
            $album = PCGALLERY_CLASS_PhotoBridge::getInstance()->getAlbum($this->userId);
            $albumId = $album["id"];
        }
        else
        {
            $albumId = BOL_PreferenceService::getInstance()->getPreferenceValue("pcgallery_album", $this->userId);
        }
        
        $jsCall = OW::getEventManager()->call("photo.getAddPhotoURL", array(
            "albumId" => $albumId
        ));
        
        $js = UTIL_JsGenerator::newInstance();
        $js->addScript('$(document).on("click", "#pcgallery-add-photo-btn", window[{$fncId}]);', array(
            "fncId" => $jsCall
        ));
        
        OW::getDocument()->addOnloadScript($js);
    }
    
    public function initFullGallery()
    {
        OW::getEventManager()->call("photo.init_floatbox");
    }

    public function initJs( $permissions )
    {
        
        if ( $permissions["changeAvatar"] )
        {
            $label = OW::getLanguage()->text('base', 'avatar_change');

            $script =
            '$("[data-outlet=avatar-change]", "#' . $this->uniqId . '").click(function() {
                document.avatarFloatBox = OW.ajaxFloatBox(
                    "BASE_CMP_AvatarChange",
                    { params : { step : 1 } },
                    { width : 749, title: ' . json_encode($label) . '}
                );
            });

            OW.bind("base.avatar_cropped", function(data){
                if ( data.bigUrl != undefined ) {
                    $("[data-outlet=avatar]", "#' . $this->uniqId . '").css({ "background-image" : "url(" + data.bigUrl + ")" });
                }
            });
            ';

            OW::getDocument()->addOnloadScript($script);
        }
        
        if ( $permissions["approveAvatar"] && ($this->avatarDto && $this->avatarDto->status != "active") )
        {
            $script = ' window.avartar_arrove_request = false;
                $("[data-outlet=approve-avatar]", "#' . $this->uniqId . '").click(function(){

                    if ( window.avartar_arrove_request == true )
                    {
                        return;
                    }

                    window.avartar_arrove_request = true;

                    $.ajax({
                        "type": "POST",
                        "url": '.json_encode(OW::getRouter()->urlFor('BASE_CTRL_Avatar', 'ajaxResponder')).',
                        "data": {
                            \'ajaxFunc\' : \'ajaxAvatarApprove\',
                            \'avatarId\' : '.((int)$this->avatarDto->id).'
                        },
                        "success": function(data){
                            if ( data.result == true )
                            {
                                if ( data.message )
                                {
                                    OW.info(data.message);
                                }
                                else
                                {
                                    OW.info('.json_encode(OW::getLanguage()->text('base', 'avatar_has_been_approved')).');
                                }

                                $("[data-outlet=approve-overlay]", "#' . $this->uniqId . '").remove();
                                $("[data-outlet=approve-avatar-w]", "#' . $this->uniqId . '").remove();
                            }
                            else
                            {
                                if ( data.error )
                                {
                                    OW.info(data.error);
                                }
                            }
                        },
                        "complete": function(){
                            window.avartar_arrove_request = false;
                        },
                        "dataType": "json"
                    });
                }); ';

            OW::getDocument()->addOnloadScript($script);
        }
    }
    
    public function onBeforeRender()
    {
        parent::onBeforeRender();

        $permissions = $this->getPemissions();
        
        PCGALLERY_CLASS_PhotoBridge::getInstance()->initFloatbox();

        $staticUrl = OW::getPluginManager()->getPlugin('pcgallery')->getStaticUrl();
        OW::getDocument()->addStyleSheet($staticUrl . 'style.css');
        OW::getDocument()->addScript($staticUrl . 'script.js');

        $this->assign("avatarApproval", $this->avatarDto && $this->avatarDto->status != "active");
        $this->initJs($permissions);

        $toolbar = new BASE_CMP_ProfileActionToolbar($this->userId);
        $this->addComponent('actionToolbar', $toolbar);

        $this->assign('uniqId', $this->uniqId);
        $this->assign('user', $this->getUserInfo());
        $this->assign('permissions', $permissions);

        $photos = $permissions["view"] ? $this->getPhotos() : array();
        $this->assign('empty', empty($photos));
        
        if ( empty($photos) )
        {
            $this->initEmptyGallery();
        }
        else
        {
            $this->initFullGallery();
        } 
        
        $this->assign('photos', $photos);

        $source = BOL_PreferenceService::getInstance()->getPreferenceValue("pcgallery_source", $this->userId);
        
        $settings = array(
            "changeInterval" => self::PHOTO_CHANGE_INTERVAL,
            "userId" => $this->userId,
            "listType" => $source == "all" ? "userPhotos" : "albumPhotos"
        );

        $js = UTIL_JsGenerator::newInstance();
        $js->callFunction(array('PCGALLERY', 'init'), array(
            $this->uniqId,
            $settings,
            $photos
        ));

        OW::getDocument()->addOnloadScript($js);
        
        OW::getLanguage()->addKeyForJs("pcgallery", "setting_fb_title");
    }
}