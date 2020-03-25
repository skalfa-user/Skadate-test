<?php

/**
 * Copyright (c) 2009, Skalfa LLC
 * All rights reserved.
 *
 * ATTENTION: This commercial software is intended for use with Oxwall Free Community Software http://www.oxwall.org/ and is licensed under SkaDate Exclusive License by Skalfa LLC.
 *
 * Full text of this license can be found at http://www.skadate.com/sel.pdf
 */
OW::getRouter()->addRoute(
    new OW_Route('billingstripe.order_form', 'stripe/order', 'BILLINGSTRIPE_MCTRL_Action', 'orderForm')
);

OW::getRouter()->addRoute(
    new OW_Route('billingstripe.handler', 'billing-stripe/handler', 'BILLINGSTRIPE_MCTRL_Action', 'handler')
);

OW::getRouter()->addRoute(
    new OW_Route('billingstripe.webhook', 'billing-stripe/webhook', 'BILLINGSTRIPE_MCTRL_Action', 'webhook')
);

BILLINGSTRIPE_CLASS_EventHandler::getInstance()->genericInit();

$dir = OW::getPluginManager()->getPlugin('billingstripe')->getClassesDir();
require_once $dir . 'stripe' . DS . 'lib' . DS . 'Stripe.php';
