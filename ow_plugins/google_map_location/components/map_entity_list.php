<?php
/**
 * Copyright (c) 2013, Podyachev Evgeny <joker.OW2@gmail.com>
 * All rights reserved.

 * ATTENTION: This commercial software is intended for use with Oxwall Free Community Software http://www.oxwall.org/
 * and is licensed under Oxwall Store Commercial License.
 * Full text of this license can be found at http://www.oxwall.org/store/oscl
 */

/**
 * @author Podyachev Evgeny <joker.OW2@gmail.com>
 * @package ow_plugins.google_maps_location.components
 * @since 1.0
 */

class GOOGLELOCATION_CMP_MapEntityList extends OW_Component
{
    protected $label;
    protected $url = '';
    protected $IdList = array();
    protected $display = false;
    protected $count = 0;
    protected $backUri = null;
    
    const DISPLAY_COUNT = 5;

    public function __construct( $IdList, $lat, $lng, $backUri = null )
    {
        $this->IdList = $IdList;
        $this->lat = (float)$lat;
        $this->lng = (float)$lng;
        $this->count = count($IdList);

        $this->setBackUri($backUri);
        
        if ( count($IdList) > self::DISPLAY_COUNT )
        {
            $hash = GOOGLELOCATION_BOL_LocationService::getInstance()->saveEntityListToSession($IdList);

            $this->display = true;
            $this->label = OW::getLanguage()->text('googlelocation', 'map_user_list_view_all_button_label', array( 'count' => count($IdList) ) );
            $this->url = ow::getRouter()->urlForRoute('googlelocation_user_list', array( 'lat' => $this->lat, 'lng' => $this->lng, 'hash' => $hash ) );
        }
        
        parent::__construct();

        $this->template = OW::getPluginManager()->getPlugin('googlelocation')->getCmpViewDir() . 'map_entity_list.html';
    }

    public function getBackUrl()
    {
        return OW_URL_HOME . $this->backUri;
    }

    public function getBackUri()
    {
        return $this->backUri;
    }

    public function setBackUri( $uri )
    {
        $this->backUri = $uri;
    }

    public function setViewMoreUrl( $url )
    {
        $this->url = $url;
    }
    
    public function setViewMoreLabel( $label )
    {
        $this->label = $label;
    }
    
    public function setDisplayViewMoreButton( $display )
    {
        $this->display = $display;
    }

    public function onBeforeRender()
    {
        parent::onBeforeRender();

        if ( !empty($this->url) && !empty($this->backUri) )
        {
            $this->url = OW::getRequest()->buildUrlQueryString( $this->url, array( 'backUri' => $this->getBackUri() ) );
        }
        
        $this->addComponent('entityList', $this->getListCmp());
        $this->assign('url', $this->url);
        $this->assign('viewAllLabel', $this->label);
        $this->assign('displayViewAllButton', $this->display);
    }
    
    protected function getListCmp()
    {
        $new = new BASE_CMP_MiniAvatarUserList(array_slice($this->IdList, 0, self::DISPLAY_COUNT));
        
        switch(true)
        {
            case $this->count <= 8:
                    $new->setCustomCssClass('ow_big_avatar');
                break;
            default:
                    //$new->setCustomCssClass(BASE_CMP_MiniAvatarUserList::CSS_CLASS_MINI_AVATAR);
                break;
        }
        
        return $new;
    }
}