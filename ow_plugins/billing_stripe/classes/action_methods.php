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

trait BILLINGSTRIPE_CLASS_ActionMethods
{
    public function orderForm()
    {
        if ( !OW::getUser()->isAuthenticated() )
        {
            throw new AuthenticateException();
        }

        $billingService = BOL_BillingService::getInstance();
        $lang = OW::getLanguage();

        $sale = $billingService->getSessionSale();

        if ( !$sale )
        {
            $url = $billingService->getSessionBackUrl();
            if ( $url != null )
            {
                OW::getFeedback()->warning($lang->text('base', 'billing_order_canceled'));
                $billingService->unsetSessionBackUrl();
                $this->redirect($url);
            }
            else
            {
                $this->redirect($billingService->getOrderFailedPageUrl());
            }
        }

        $this->assign('startYear', date("Y"));
        $this->assign('endYear', date("Y") + 15);

        $billingService = BOL_BillingService::getInstance();
        $gwKey = BILLINGSTRIPE_CLASS_StripeAdapter::GATEWAY_KEY;

        $requireData = $billingService->getGatewayConfigValue($gwKey, 'requireData');
        $this->assign('requireData', $requireData);

        $formId = uniqid("stripe-form-");
        $this->assign('formId', $formId);
        $this->assign('formAction', OW::getRouter()->urlForRoute('billingstripe.handler'));
        $this->assign('countries', BILLINGSTRIPE_CLASS_StripeAdapter::getCountryList());

        $productString = $lang->text(
            'billingstripe',
            'product_string',
            array(
                'product' => strip_tags($sale->entityDescription)
            )
        );
        $this->assign('productString', $productString);

        $this->setPageHeading($lang->text('billingstripe', 'pay_via_credit_card'));

        OW::getDocument()->addScript("https://js.stripe.com/v1/");

        $publicKey = BILLINGSTRIPE_CLASS_StripeAdapter::getPublicKey();

        $script =
            'Stripe.setPublishableKey('.json_encode($publicKey).');

        $("#'.$formId.'").submit(function(){
            var $form = $(this);

            var cnumber = $(".c-number", $(this)).val();
            if ( !Stripe.validateCardNumber(cnumber) ) {
                OW.error('.json_encode($lang->text('billingstripe', 'card_number_invalid')).');
                $(".c-number", $(this)).focus();
                return false;
            }

            var cname = $(".c-name", $(this)).val();
            if ( !cname.length ) {
                OW.error('.json_encode($lang->text('billingstripe', 'name_on_card_required')).');
                $(".c-name", $(this)).focus();
                return false;
            }

            var cvc = $(".c-cvc", $(this)).val();
            if ( !Stripe.validateCVC(cvc) ) {
                OW.error('.json_encode($lang->text('billingstripe', 'cvc_invalid')).');
                $(".c-cvc", $(this)).focus();
                return false;
            }

            var month = $(".c-expiry-month", $(this)).val();
            var year = $(".c-expiry-year", $(this)).val();

            if ( !Stripe.validateExpiry(month, year) ) {
                OW.error('.json_encode($lang->text('billingstripe', 'exp_date_invalid')).');
                return false;
            }';

        if ( $requireData )
        {
            $script .=
                'var b_country = $(".billing-country").val();
            if ( !b_country.length )
            {
                OW.error('.json_encode($lang->text('billingstripe', 'country_required')).');
                return false;
            }

            var b_state = $(".billing-state").val();
            if ( !b_state.length )
            {
                OW.error('.json_encode($lang->text('billingstripe', 'state_required')).');
                return false;
            }

            var b_address = $(".billing-address1").val();
            if ( !b_address.length )
            {
                OW.error('.json_encode($lang->text('billingstripe', 'address_required')).');
                return false;
            }

            var b_zip = $(".billing-zip").val();
            if ( !b_zip.length )
            {
                OW.error('.json_encode($lang->text('billingstripe', 'zip_code_required')).');
                return false;
            }';
        }

        $script .=
            'OW.inProgressNode($form.find("input[type=submit]"));
            Stripe.createToken({
                number: cnumber,
                cvc: cvc,
                exp_month: month,
                exp_year: year,
                name: cname';

        if ( $requireData )
        {
            $script .= ',
                    address_line1: b_address,
                    address_state: b_state,
                    address_zip: b_zip,
                    address_country: b_country';
        }

        $script .=
            '}, stripeResponseHandler);
            return false;
        });

        function stripeResponseHandler(status, response)
        {
            if ( response.error ) {
                OW.error(response.error.message);
                OW.activateNode($("#'.$formId.'").find("input[type=submit]"));
                return false;
            } else {
                var form = $("#'.$formId.'");
                var token = response["id"];
                form.append("<input type=\"hidden\" name=\"stripeToken\" value=\"" + token + "\" />");
                form.get(0).submit();
            }
        }
        ';

        OW::getDocument()->addOnloadScript($script);
    }

    public function handler()
    {

        $billingService = BOL_BillingService::getInstance();
        $lang = OW::getLanguage();

        $returnUrl = $billingService->getSessionBackUrl();
        $formUrl = OW::getRouter()->urlForRoute('billingstripe.order_form');

        $sale = $billingService->getSessionSale();
        if ( !$sale )
        {
            if ( $returnUrl != null )
            {
                OW::getFeedback()->warning($lang->text('base', 'billing_order_canceled'));
                $billingService->unsetSessionBackUrl();
                $this->redirect($returnUrl);
            }
            else
            {
                $this->redirect($billingService->getOrderFailedPageUrl());
            }
        }

        if ( empty($_POST['stripeToken']) )
        {
            $this->redirect($formUrl);
        }

        $userId = OW::getUser()->getId();
        if ( !$userId )
        {
            throw new AuthenticateException();
        }

        $token = trim($_POST['stripeToken']);



        $adapter = new BILLINGSTRIPE_CLASS_StripeAdapter();
        $productAdapter = $billingService->getProductAdapter($sale->entityKey);
        $stripeService = BILLINGSTRIPE_BOL_Service::getInstance();

        Stripe::setApiKey($adapter->getSecretKey());

        // check if customer stored in db
        $dbCustomer = $stripeService->findCustomerByUserId($userId);
        $update = false;
        $customer = null;
        if ( $dbCustomer )
        {
            try
            {
                $customer = Stripe_Customer::retrieve($dbCustomer->stripeCustomerId);
                if ( $customer )
                {
                    $update = true;
                }
            }
            catch ( Exception $e ) { }
        }

        $logger = OW::getLogger('billingstripe.order');
        $data = array('description' => OW::getUser()->getEmail(), 'source' => $token);

        if ( $update ) // update customer with new card token
        {
            try
            {
                $customer->card = $token;
                $customer->save();

                $logger->addEntry(print_r($customer, true), 'billingstripe.customer-update');
            }
            catch ( Exception $e )
            {
                $logger->addEntry("Data: " . print_r($data, true) . "\n" . $e->getMessage(), 'billingstripe.customer-update-error');
                $logger->writeLog();
                OW::getFeedback()->error($e->getMessage());
                $this->redirect($formUrl);
            }
        }
        else // create new customer with card token
        {
            try
            {
                $customer = Stripe_Customer::create($data);
                $logger->addEntry(print_r($customer, true), 'billingstripe.customer-create');
            }
            catch ( Exception $e )
            {
                $logger->addEntry("Data: " . print_r($data, true) . "\n" . $e->getMessage(), 'billingstripe.customer-create-error');
                $logger->writeLog();

                OW::getFeedback()->error($e->getMessage());
                $this->redirect($formUrl);
            }
        }
        $arrayCus = $customer->__toArray(true);

        if(!$customer->default_card)
        {
            $customer->default_card = $arrayCus['sources']['data'][0]['id'];
        }

        $card = $customer->sources->data;
        $customer->cards = $customer->sources->data;
        //  check if customer has active card on file
        if ( !$card )
        {
            OW::getFeedback()->error($lang->text('billingstripe', 'card_not_valid'));
            $this->redirectToAction('orderForm');
        }


        $stripeService->storeCustomer($customer, $userId, $dbCustomer);

        $paymentDone = false;
        $transactionId = null;
        $errorMessage = null;

        if ( !$sale->recurring ) // simple card charge
        {
            $data = array(
                "amount" => $sale->totalAmount * 100,
                "currency" => strtolower($billingService->getActiveCurrency()),
                "customer" => $customer->id,
                'metadata' => array('hash' => $sale->hash)
            );

            try {

                $charge = Stripe_Charge::create($data);

                $paymentDone = $charge->paid;

                if ( $paymentDone )
                {
                    $transactionId = $charge->id;
                    $charge->card = $arrayCus['sources']['data'][0]['id'];

                    // store charge to db

                    $stripeService->storeChargeToDb($userId, $charge, $sale);
                }
                else
                {
                    $errorMessage = $charge->failure_message;
                }

                $logger->addEntry(print_r($charge, true), 'billingstripe.customer-charge');
            }
            catch ( Exception $e )
            {
                $logger->addEntry("Data: " . print_r($data, true) . "\n" . $e->getMessage(), 'billingstripe.customer-charge-error');
                $logger->writeLog();

                OW::getFeedback()->error($e->getMessage());
                $this->redirect($formUrl);
            }
        }
        else // recurring subscription
        {
            $productId = strtoupper($productAdapter->getProductKey() . '_' . $sale->entityId);

            // check plan exists
            try
            {
                $plan = Stripe_Plan::retrieve($productId);

                if ( $plan )
                {
                    $data = array('plan' => $productId, 'metadata' => array('hash' => $sale->hash));
                    if ( $billingService->getGatewayConfigValue(BILLINGSTRIPE_CLASS_StripeAdapter::GATEWAY_KEY, 'sandboxMode'))
                    {
                        $data['trial_end'] = time() + 60;
                    }

                    // add subscription
                    try
                    {
                        $subscription = $customer->subscriptions->create($data);

                        $paymentDone = true;
                        $transactionId = $subscription->id;

                        // store subscription to db
                        $stripeService->storeSubscription($userId, $subscription, $sale);

                        $logger->addEntry(print_r($subscription, true), 'billingstripe.subscription-create');
                    }
                    catch ( Exception $e )
                    {
                        $logger->addEntry("Data: " . print_r($data, true) . "\n" . $e->getMessage(), 'billingstripe.subscription-create-error');
                        $logger->writeLog();

                        OW::getFeedback()->error($e->getMessage());
                        $this->redirect($formUrl);
                    }
                }
            }
            catch ( Exception $e )
            {
                $logger->addEntry("Product ID: " . $productId . "\n" . $e->getMessage(), 'billingstripe.plan-retrieve-error');
                $logger->writeLog();

                OW::getFeedback()->error($e->getMessage());
                $this->redirect($formUrl);
            }
        }

        $logger->writeLog();

        if (  $paymentDone )
        {
            if ( !$billingService->saleDelivered($transactionId, $sale->gatewayId) )
            {
                $sale->transactionUid = $transactionId;

                if ( $billingService->verifySale($adapter, $sale) )
                {
                    $sale = $billingService->getSaleById($sale->id);

                    if ( $productAdapter )
                    {
                        $billingService->deliverSale($productAdapter, $sale);

                        $billingService->unsetSessionSale();

                        OW::getFeedback()->info($lang->text('base', 'billing_order_completed_successfully'));
                    }
                }
            }

            $this->redirect($returnUrl);
        }
        else
        {
            OW::getFeedback()->error($errorMessage);
            $this->redirect($formUrl);
        }
    }

    public function webhook()
    {

        $dir = OW::getPluginManager()->getPlugin('billingstripe')->getClassesDir();
        require_once $dir . 'stripe' . DS . 'lib' . DS . 'Stripe.php';

        $adapter = new BILLINGSTRIPE_CLASS_StripeAdapter();
        $stripeService = BILLINGSTRIPE_BOL_Service::getInstance();
        $billingService = BOL_BillingService::getInstance();

        Stripe::setApiKey($adapter->getSecretKey());

        $logger = OW::getLogger('billingstripe.webhook');

        $input = @file_get_contents("php://input");

        $eventJson = json_decode($input);

        $logger->addEntry(print_r($eventJson, true), 'billingstripe.webhook-json');

        $eventId = $eventJson->id;

        try
        {
            $event = Stripe_Event::retrieve($eventId);

            $logger->addEntry(print_r($event, true), 'billingstripe.event-retrieve');
        }
        catch ( Exception $e )
        {
            $logger->addEntry("Event ID: " . $eventId . "\n" . $e->getMessage(), 'billingstripe.event-retrieve-error');
            $logger->writeLog();

            $this->send200Status();
        }

        if ( !empty($event) && $event->type == 'invoice.payment_succeeded' )
        {
            $customerId = $event->data->object->customer;

            $customer = $stripeService->findCustomerByStripeId($customerId);

            if ( !$customer )
            {
                $logger->addEntry("Customer not found, ID: " . $customerId, 'billingstripe.customer-not-found');
                $logger->writeLog();

                $this->send200Status();
            }

            $invoiceId = $event->data->object->id;

            try
            {
                $invoice = Stripe_Invoice::retrieve($invoiceId);

                $logger->addEntry(print_r($invoice, true), 'billingstripe.invoice-retrieve');
            }
            catch ( Exception $e )
            {
                $logger->addEntry("Invoice ID: " . $invoiceId . "\n" . $e->getMessage(), 'billingstripe.invoice-retrieve-error');
                $logger->writeLog();

                $this->send200Status();
            }

            if ( !empty($invoice) )
            {
                foreach ( $invoice->lines->data as $line )
                {
                    if ( $line->type != 'subscription')
                    {
                        $logger->addEntry($line->type, 'line type');
                        continue;
                    }

                    $subscriptionId = $line->subscription;
                    $subscription = $stripeService->findSubscriptionByStripeId($subscriptionId);

                    if ( !$subscription )
                    {
                        $logger->addEntry("Subscription not found, ID: " . $subscriptionId, 'billingstripe.subscription-not-found');
                        $logger->writeLog();

                        $this->send200Status();
                    }

                    $sale = $billingService->getSaleById($subscription->saleId);

                    if ( !strlen($subscription->stripeInitialInvoiceId) ) // check if it is first invoice and do not track
                    {

                        $subscription->stripeInitialInvoiceId = $invoiceId;
                        $stripeService->updateSubscription($subscription, $sale);

                        $logger->addEntry("Initial invoice, ID: " . $invoiceId, 'billingstripe.subscription-initial-invoice');
                        $logger->writeLog();

                        $this->send200Status();
                    }

                    $sale = $billingService->getSaleById($subscription->saleId);

                    // register rebill
                    $rebillTransId = $invoice->id;

                    $gateway = $billingService->findGatewayByKey(BILLINGSTRIPE_CLASS_StripeAdapter::GATEWAY_KEY);

                    if ( $billingService->saleDelivered($rebillTransId, $gateway->id) )
                    {
                        $logger->addEntry("Rebill already delivered, transaction ID: " . $rebillTransId, 'billingstripe.subscription-rebill-delivered');
                        $logger->writeLog();

                        $this->send200Status();
                    }

                    $rebillSaleId = $billingService->registerRebillSale($adapter, $sale, $rebillTransId);

                    $logger->addEntry("Rebill sale ID: " . (int) $rebillSaleId, 'billingstripe.subscription-rebill-sale-id');

                    if ( $rebillSaleId )
                    {
                        $rebillSale = $billingService->getSaleById($rebillSaleId);

                        $productAdapter = $billingService->getProductAdapter($rebillSale->entityKey);
                        if ( $productAdapter )
                        {
                            $billingService->deliverSale($productAdapter, $rebillSale);

                            $logger->addEntry("Rebill registered, ID: " . $rebillSaleId, 'billingstripe.subscription-rebill');
                        }
                    }
                }
            }
        }

        $logger->writeLog();

        $this->send200Status();
    }

    private function send200Status()
    {
        header("HTTP/1.1 200 OK");

        exit;
    }
}