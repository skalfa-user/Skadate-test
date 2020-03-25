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

/**
 * Stripe Billing Service Class.
 *
 * @author Egor Bulgakov <egor.bulgakov@gmail.com>
 * @package ow.ow_plugins.billing_stripe.bol
 * @since 1.0
 */
final class BILLINGSTRIPE_BOL_Service
{
    /**
     * @var BILLINGSTRIPE_BOL_CustomerDao
     */
    private $customerDao;
    /**
     * @var BILLINGSTRIPE_BOL_ChargeDao
     */
    private $chargeDao;
    /**
     * @var BILLINGSTRIPE_BOL_SubscriptionDao
     */
    private $subscriptionDao;

    /**
     * @var BOL_BillingService
     */
    protected $billingService;

    /**
     * @var OW_Log
     */
    protected $logger;

    /**
     * @var OW_Language
     */
    protected $lang;

    /**
     * Class instance
     *
     * @var BILLINGSTRIPE_BOL_Service
     */
    private static $classInstance;

    /**
     * Class constructor
     */
    private function __construct()
    {
        $this->customerDao = BILLINGSTRIPE_BOL_CustomerDao::getInstance();
        $this->chargeDao = BILLINGSTRIPE_BOL_ChargeDao::getInstance();
        $this->subscriptionDao = BILLINGSTRIPE_BOL_SubscriptionDao::getInstance();
        $this->billingService = BOL_BillingService::getInstance();
        $this->lang = OW::getLanguage();
        $this->logger = OW::getLogger('billing_stripe');
    }

    /**
     * Returns class instance
     *
     * @return BILLINGSTRIPE_BOL_Service
     */
    public static function getInstance()
    {
        if ( null === self::$classInstance )
        {
            self::$classInstance = new self();
        }

        return self::$classInstance;
    }

    /**
     * @param $userId
     * @return BILLINGSTRIPE_BOL_Customer
     */
    public function findCustomerByUserId( $userId )
    {
        if ( !$userId )
        {
            return null;
        }

        return $this->customerDao->findByUserId($userId);
    }

    /**
     * @param $id
     * @return BILLINGSTRIPE_BOL_Customer
     */
    public function findCustomerByStripeId( $id )
    {
        if ( !mb_strlen($id) )
        {
            return null;
        }

        return $this->customerDao->findByStripeId($id);
    }

    /**
     * @param $id
     * @return BILLINGSTRIPE_BOL_Subscription
     */
    public function findSubscriptionByStripeId( $id )
    {
        if ( !mb_strlen($id) )
        {
            return null;
        }

        return $this->subscriptionDao->findByStripeId($id);
    }

    /**
     * @param BILLINGSTRIPE_BOL_Customer $dbcustomer
     * @param Stripe_Customer $customer
     * @param $userId
     * @return BILLINGSTRIPE_BOL_Customer
     */
    public function storeCustomer( $customer, $userId, BILLINGSTRIPE_BOL_Customer $dbcustomer = null )
    {
        if ( !$dbcustomer )
        {
            $dbcustomer = new BILLINGSTRIPE_BOL_Customer();
            $dbcustomer->userId = $userId;
        }

        $dbcustomer->stripeCustomerId = $customer->id;
        $dbcustomer->defaultCard = $customer->default_card;
        $dbcustomer->createStamp = $customer->created;
        $dbcustomer->subscriptions = $customer->subscriptions;
        $dbcustomer->cards = $customer->cards;
        $dbcustomer->currency = $customer->currency;

        $this->customerDao->save($dbcustomer);

        return $dbcustomer;

    }

    /**
     * @param $userId
     * @param BILLINGSTRIPE_BOL_Charge $charge
     * @param $sale
     */
    public function storeChargeToDb( $userId, $charge, $sale )
    {
        $dbCharge = new BILLINGSTRIPE_BOL_Charge();
        $dbCharge->userId = $userId;
        $dbCharge->stripeChargeId = $charge->id;
        $dbCharge->stripeCustomerId = $charge->customer;
        $dbCharge->createStamp = $charge->created;
        $dbCharge->amount = $charge->amount;
        $dbCharge->currency = $charge->currency;
        $dbCharge->card = $charge->card;
        $dbCharge->saleId = $sale->id;

        $this->chargeDao->save($dbCharge);
    }

    /**
     * @param $userId
     * @param BILLINGSTRIPE_BOL_Subscription $subscription
     * @param $sale
     */
    public function storeSubscription( $userId, $subscription, $sale )
    {
        $dbSubscription = new BILLINGSTRIPE_BOL_Subscription();
        $dbSubscription->userId = $userId;
        $dbSubscription->saleId = $sale->id;
        $dbSubscription->stripeSubscriptionId = $subscription->id;
        $dbSubscription->stripeCustomerId = $subscription->customer;
        $dbSubscription->startStamp = $subscription->start;
        $dbSubscription->currentStartStamp = $subscription->current_period_start;
        $dbSubscription->currentEndStamp = $subscription->current_period_end;
        $dbSubscription->plan = $subscription->plan;

        $this->subscriptionDao->save($dbSubscription);
    }

    /**
     * @param $subscription
     * @param $sale
     */
    public function updateSubscription( $subscription, $sale )
    {
        $dbSubscription = new BILLINGSTRIPE_BOL_Subscription();
        $dbSubscription->id = $subscription->id;
        $dbSubscription->userId = $subscription->userId;
        $dbSubscription->saleId = $sale->id;
        $dbSubscription->stripeSubscriptionId = $subscription->stripeSubscriptionId;
        $dbSubscription->stripeCustomerId = $subscription->stripeCustomerId;
        $dbSubscription->startStamp = $subscription->startStamp;
        $dbSubscription->currentStartStamp = $subscription->currentStartStamp;
        $dbSubscription->currentEndStamp = $subscription->currentEndStamp;
        $dbSubscription->plan = $subscription->plan;

        $dbSubscription->stripeInitialInvoiceId = $subscription->stripeInitialInvoiceId;

        $this->subscriptionDao->save($dbSubscription);
    }


    public function createToken( $cardDetails, $apiKey )
    {
        $expDate =  explode('-', $cardDetails['expiration_date']);

        Stripe::setApiKey($apiKey);

        $tokenData = [
            'card' => [
                'name' => $cardDetails['card_name'],
                'number' => $cardDetails['card_number'],
                'cvc' => $cardDetails['cvc'],
                'exp_month' => $expDate[1],
                'exp_year' => $expDate[0],
            ]
        ];

        $token = Stripe_Token::create($tokenData);

        if( empty($token) )
        {
            $this->logger->addEntry('token_was_not_created', 'create_token_operation_service');
        }

        return $token->id;

    }

    /**
     * @param $token
     * @param BOL_BillingSale $sale
     * @return array
     */
    public function processApplicationPayment($token, $sale )
    {
        $lang = OW::getLanguage();

        $adapter = new BILLINGSTRIPE_CLASS_StripeAdapter();
        $productAdapter = $this->billingService->getProductAdapter($sale->entityKey);

        $userId = $sale->userId;

        Stripe::setApiKey($adapter->getSecretKey());

        // check if customer stored in db
        $dbCustomer = $this->findCustomerByUserId($userId);
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

                return ['status' => 'error', 'message' => $e->getMessage()];
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

                return ['status' => 'error', 'message' => $e->getMessage()];
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
            return ['status' => 'error', 'message' => 'card_not_valid'];
        }


        $this->storeCustomer($customer, $userId, $dbCustomer);

        $paymentDone = false;
        $transactionId = null;
        $errorMessage = null;


        if ( !$sale->recurring ) // simple card charge
        {
            $data = array(
                "amount" => $sale->totalAmount * 100,
                "currency" => strtolower( $this->billingService->getActiveCurrency()),
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

                    $this->storeChargeToDb($userId, $charge, $sale);
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

                return ['status' => 'error', 'message' => $e->getMessage()];
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
                    if ( $this->billingService->getGatewayConfigValue(BILLINGSTRIPE_CLASS_StripeAdapter::GATEWAY_KEY, 'sandboxMode'))
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
                        $this->storeSubscription($userId, $subscription, $sale);

                        $logger->addEntry(print_r($subscription, true), 'billingstripe.subscription-create');
                    }
                    catch ( Exception $e )
                    {
                        $logger->addEntry("Data: " . print_r($data, true) . "\n" . $e->getMessage(), 'billingstripe.subscription-create-error');
                        $logger->writeLog();

                        return ['status' => 'error', 'message' => $e->getMessage()];
                    }
                }
            }
            catch ( Exception $e )
            {
                $logger->addEntry("Product ID: " . $productId . "\n" . $e->getMessage(), 'billingstripe.plan-retrieve-error');
                $logger->writeLog();

                return ['status' => 'error', 'message' => $e->getMessage()];
            }
        }

        $logger->writeLog();



        if (  $paymentDone )
        {
            if ( !$this->billingService->saleDelivered($transactionId, $sale->gatewayId) )
            {
                $sale->transactionUid = $transactionId;

                if ( $this->billingService->verifySale($adapter, $sale) )
                {
                    $sale = $this->billingService->getSaleById($sale->id);

                    if ( $productAdapter )
                    {
                        $this->billingService->deliverSale($productAdapter, $sale);

                        $this->billingService->unsetSessionSale();

                        OW::getFeedback()->info($lang->text('base', 'billing_order_completed_successfully'));
                    }
                }
            }

            return ['status' => 'success', 'message' => null];
        }
        else
        {
            return ['status' => 'error', 'message' => $errorMessage];
        }

    }


}