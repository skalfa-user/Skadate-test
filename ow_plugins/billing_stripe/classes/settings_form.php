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

class BILLINGSTRIPE_CLASS_SettingsForm extends Form
{
    public function __construct()
    {
        parent::__construct('settings-form');

        $lang = OW::getLanguage();

        $sandboxMode = new CheckboxField('sandboxMode');
        $sandboxMode->setLabel($lang->text('billingstripe', 'sandbox_mode'));
        $this->addElement($sandboxMode);

        $requireData = new CheckboxField('requireData');
        $requireData->setLabel($lang->text('billingstripe', 'require_data'));
        $this->addElement($requireData);

        $livePK = new TextField('livePK');
        $livePK->setLabel($lang->text('billingstripe', 'live_public_key'));
        $this->addElement($livePK);

        $liveSK = new TextField('liveSK');
        $liveSK->setLabel($lang->text('billingstripe', 'live_secret_key'));
        $this->addElement($liveSK);

        $testPK = new TextField('testPK');
        $testPK->setLabel($lang->text('billingstripe', 'test_public_key'));
        $this->addElement($testPK);

        $testSK = new TextField('testSK');
        $testSK->setLabel($lang->text('billingstripe', 'test_secret_key'));
        $this->addElement($testSK);

        // submit
        $submit = new Submit('save');
        $submit->setValue($lang->text('billingstripe', 'btn_save'));
        $this->addElement($submit);
    }
}