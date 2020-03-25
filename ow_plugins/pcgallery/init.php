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

function pcgallery_get_content( BASE_CLASS_EventCollector $event )
{
    $params = $event->getParams();

    if ( $params['placeName'] != "profile" )
    {
        return;
    }

    if ( !PCGALLERY_CLASS_PhotoBridge::getInstance()->isActive() )
    {
        return;
    }
    
    $cmp = OW::getClassInstance('PCGALLERY_CMP_Gallery', $params['entityId']);
    $event->add($cmp->render());
}

OW::getEventManager()->bind('base.widget_panel.content.top', 'pcgallery_get_content');
OW::getEventManager()->bind('base.widget_panel_customize.content.top', 'pcgallery_get_content');

function pcgallery_class_get_instance( OW_Event $event )
{
    $params = $event->getParams();

    if ( $params['className'] != 'BASE_CMP_ProfileActionToolbar' )
    {
        return;
    }

    if ( !PCGALLERY_CLASS_PhotoBridge::getInstance()->isActive() )
    {
        return;
    }
    
    $arguments = $params['arguments'];
    $cmp = new PCGALLERY_CMP_ProfileActionToolbarMock($arguments[0]);
    $event->setData($cmp);

    return $cmp;
}
OW::getEventManager()->bind('class.get_instance', 'pcgallery_class_get_instance');

PCGALLERY_CLASS_PhotoBridge::getInstance()->init();

function pcgallery_after_plugin_activate( OW_Event $e )
{
    $params = $e->getParams();
    $pluginKey = $params['pluginKey'];

    if ( $pluginKey != 'photo' )
    {
        return;
    }
    
    $widgetService = BOL_ComponentAdminService::getInstance();
    $widgetService->deleteWidget('BASE_CMP_UserAvatarWidget');
}
OW::getEventManager()->bind(OW_EventManager::ON_AFTER_PLUGIN_ACTIVATE, "pcgallery_after_plugin_activate");

function pcgallery_before_plugin_deactivate( OW_Event $e )
{
    $params = $e->getParams();
    $pluginKey = $params['pluginKey'];

    if ( $pluginKey != 'photo' )
    {
        return;
    }
    
    $widgetService = BOL_ComponentAdminService::getInstance();

    $widget = $widgetService->addWidget('BASE_CMP_UserAvatarWidget', false);
    $widgetPlace = $widgetService->addWidgetToPlace($widget, BOL_ComponentService::PLACE_PROFILE);

    try 
    {
        $widgetService->addWidgetToPosition($widgetPlace, BOL_ComponentService::SECTION_LEFT, 0);
    }
    catch ( Exception $e ) {}
}
OW::getEventManager()->bind(OW_EventManager::ON_BEFORE_PLUGIN_DEACTIVATE, "pcgallery_before_plugin_deactivate");

// This event is triggered when album deleting, if an album is displayed on Profile Gallery then record about this will be cleared on default value
function pcgallery_before_photo_album_delete( OW_Event $event ) 
{
    $params = $event->getParams();    
    if( empty($params["id"]) ) // check the existence of  delete albums Id
    {
        return;
    }
    
    $eventAlbumFind = new OW_Event("photo.album_find", array("albumId" => $params["id"]));
    OW::getEventManager()->trigger($eventAlbumFind);
   
    $photoAlbum = $eventAlbumFind->getData(); //select info about delete albums
    $prefValue = BOL_PreferenceService::getInstance()->getPreferenceValue("pcgallery_album", $photoAlbum["userId"]);    
   
    if( $prefValue == $photoAlbum["id"] ) // if an deleting album is displayed on Profile Gallery
    {
       $pcgallerySourceInfo = BOL_PreferenceDao::getInstance()->findPreference("pcgallery_source");
       $pcgalleryAlbumInfo =  BOL_PreferenceDao::getInstance()->findPreference("pcgallery_album"); // select default values of preferences
       BOL_PreferenceService::getInstance()->savePreferenceValue("pcgallery_album", $pcgalleryAlbumInfo->defaultValue, $photoAlbum["userId"]);
       BOL_PreferenceService::getInstance()->savePreferenceValue("pcgallery_source", $pcgallerySourceInfo->defaultValue, $photoAlbum["userId"]); 
       // reset values of profile gallery on default 
    }     
}
OW::getEventManager()->bind('photo.before_album_delete', 'pcgallery_before_photo_album_delete');
