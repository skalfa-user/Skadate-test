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

$sql = "CREATE TABLE IF NOT EXISTS `".OW_DB_PREFIX."billingstripe_customer` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `userId` int(11) NOT NULL,
  `stripeCustomerId` varchar(50) NOT NULL,
  `defaultCard` varchar(50) NOT NULL,
  `createStamp` int(11) NOT NULL,
  `subscriptions` mediumtext NOT NULL,
  `cards` mediumtext NOT NULL,
  `currency` varchar(10) NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `userId` (`userId`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;";

OW::getDbo()->query($sql);

$sql = "CREATE TABLE IF NOT EXISTS `".OW_DB_PREFIX."billingstripe_charge` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `userId` int(11) NOT NULL,
  `saleId` int(11) NOT NULL,
  `stripeChargeId` varchar(50) NOT NULL,
  `stripeCustomerId` varchar(50) NOT NULL,
  `createStamp` int(11) NOT NULL,
  `amount` float(9,2) NOT NULL,
  `currency` varchar(10) NOT NULL,
  `card` mediumtext NOT NULL,
  PRIMARY KEY (`id`),
  KEY `userId` (`userId`),
  KEY `saleId` (`saleId`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;";

OW::getDbo()->query($sql);

$sql = "CREATE TABLE IF NOT EXISTS `".OW_DB_PREFIX."billingstripe_subscription` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `userId` int(11) NOT NULL,
  `saleId` int(11) NOT NULL,
  `stripeSubscriptionId` varchar(50) NOT NULL,
  `stripeCustomerId` varchar(50) NOT NULL,
  `stripeInitialInvoiceId` VARCHAR(50) NULL DEFAULT NULL,
  `startStamp` int(11) NOT NULL,
  `currentStartStamp` int(11) NOT NULL,
  `currentEndStamp` int(11) NOT NULL,
  `plan` mediumtext NOT NULL,
  PRIMARY KEY (`id`),
  KEY `userId` (`userId`,`saleId`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;";

OW::getDbo()->query($sql);


$billingService = BOL_BillingService::getInstance();

$gateway = new BOL_BillingGateway();
$gateway->gatewayKey = 'billingstripe';
$gateway->adapterClassName = 'BILLINGSTRIPE_CLASS_StripeAdapter';
$gateway->active = 0;
$gateway->mobile = 1;
$gateway->recurring = 1;
$gateway->dynamic = 0;
$gateway->currencies = 'USD,EUR,GBP,CAD,AUD,CHF,DKK,NOK,SEK';

$billingService->addGateway($gateway);

$billingService->addConfig('billingstripe', 'livePK', '');
$billingService->addConfig('billingstripe', 'testPK', '');
$billingService->addConfig('billingstripe', 'liveSK', '');
$billingService->addConfig('billingstripe', 'testSK', '');
$billingService->addConfig('billingstripe', 'sandboxMode', '0');
$billingService->addConfig('billingstripe', 'requireData', '1');

OW::getPluginManager()->addPluginSettingsRouteName('billingstripe', 'billingstripe.admin');

$path = OW::getPluginManager()->getPlugin('billingstripe')->getRootDir() . 'langs.zip';
OW::getLanguage()->importPluginLangs($path, 'billingstripe');