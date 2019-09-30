<?php

namespace IO\Extensions\Constants;

use IO\Helper\MemoryCache;
use IO\Helper\RouteConfig;
use IO\Helper\Utils;
use IO\Services\CategoryService;
use IO\Services\OrderTrackingService;
use IO\Services\SessionStorageService;
use IO\Services\UrlBuilder\CategoryUrlBuilder;
use IO\Services\UrlBuilder\UrlQuery;
use IO\Services\WebstoreConfigurationService;
use Plenty\Modules\Authorization\Services\AuthHelper;
use Plenty\Modules\Frontend\Events\FrontendLanguageChanged;
use Plenty\Modules\Order\Contracts\OrderRepositoryContract;
use Plenty\Plugin\Events\Dispatcher;
use Plenty\Plugin\Http\Request;

class ShopUrls
{
    use MemoryCache;

    /**
     * @var SessionStorageService $sessionStorageService
     */
    private $sessionStorageService;

    public $appendTrailingSlash = false;
    public $trailingSlashSuffix = "";
    public $includeLanguage     = false;

    public $basket              = "";
    public $cancellationForm    = "";
    public $cancellationRights  = "";
    public $checkout            = "";
    public $confirmation        = "";
    public $contact             = "";
    public $gtc                 = "";
    public $home                = "";
    public $legalDisclosure     = "";
    public $login               = "";
    public $myAccount           = "";
    public $passwordReset       = "";
    public $privacyPolicy       = "";
    public $registration        = "";
    public $search              = "";
    public $termsConditions     = "";
    public $wishList            = "";
    public $returns             = "";
    public $returnConfirmation  = "";
    public $changeMail          = "";
    public $newsletterOptOut    = "";

    public function __construct(Dispatcher $dispatcher, SessionStorageService $sessionStorageService)
    {
        $this->sessionStorageService = $sessionStorageService;

        $this->init($sessionStorageService->getLang());
        $dispatcher->listen(FrontendLanguageChanged::class, function(FrontendLanguageChanged $event)
        {
            $this->init($event->getLanguage());
        });
    }

    private function init($lang)
    {
        /** @var WebstoreConfigurationService $webstoreConfigurationService */
        $webstoreConfigurationService = pluginApp(WebstoreConfigurationService::class);
        $this->resetMemoryCache();
        $this->appendTrailingSlash      = UrlQuery::shouldAppendTrailingSlash();
        $this->trailingSlashSuffix      = $this->appendTrailingSlash ? '/' : '';
        $this->includeLanguage          = $lang !== $webstoreConfigurationService->getDefaultLanguage();

        $this->basket                   = $this->getShopUrl(RouteConfig::BASKET);
        $this->cancellationForm         = $this->getShopUrl(RouteConfig::CANCELLATION_FORM);
        $this->cancellationRights       = $this->getShopUrl(RouteConfig::CANCELLATION_RIGHTS);
        $this->checkout                 = $this->getShopUrl(RouteConfig::CHECKOUT);
        $this->confirmation             = $this->getShopUrl(RouteConfig::CONFIRMATION);
        $this->contact                  = $this->getShopUrl(RouteConfig::CONTACT);
        $this->gtc                      = $this->getShopUrl(RouteConfig::TERMS_CONDITIONS);

        // Homepage URL may not be used from category. Even if linked to category, the homepage url should be "/"
        $this->home                     = Utils::makeRelativeUrl('/', $this->includeLanguage);
        $this->legalDisclosure          = $this->getShopUrl(RouteConfig::LEGAL_DISCLOSURE);
        $this->login                    = $this->getShopUrl(RouteConfig::LOGIN);
        $this->myAccount                = $this->getShopUrl(RouteConfig::MY_ACCOUNT);
        $this->passwordReset            = $this->getShopUrl(RouteConfig::PASSWORD_RESET);
        $this->privacyPolicy            = $this->getShopUrl(RouteConfig::PRIVACY_POLICY);
        $this->registration             = $this->getShopUrl(RouteConfig::REGISTER);
        $this->search                   = $this->getShopUrl(RouteConfig::SEARCH);
        $this->termsConditions          = $this->getShopUrl(RouteConfig::TERMS_CONDITIONS);
        $this->wishList                 = $this->getShopUrl(RouteConfig::WISH_LIST);
        $this->returns                  = $this->getShopUrl(RouteConfig::ORDER_RETURN, "returns");
        $this->returnConfirmation       = $this->getShopUrl(RouteConfig::ORDER_RETURN_CONFIRMATION, "return-confirmation");
        $this->changeMail               = $this->getShopUrl(RouteConfig::CHANGE_MAIL);
        $this->newsletterOptOut         = $this->getShopUrl(RouteConfig::NEWSLETTER_OPT_OUT, "newsletter/unsubscribe");
    }

    public function returns($orderId, $orderAccessKey = null)
    {
        if($orderAccessKey == null) {
            $request = pluginApp(Request::class);
            $orderAccessKey = $request->get('accessKey');
        }

        $categoryId = RouteConfig::getCategoryId( RouteConfig::ORDER_RETURN );
        if ( $categoryId > 0 )
        {
            $params = [
                "orderId"        => $orderId,
                "orderAccessKey" => $orderAccessKey
            ];

            return $this->getShopUrl(RouteConfig::ORDER_RETURN, "returns", null, $params);
        }

        return $this->getShopUrl(RouteConfig::ORDER_RETURN, "returns", [$orderId, $orderAccessKey]);
    }

    public function orderPropertyFile($path)
    {
        return $this->getShopUrl(RouteConfig::ORDER_PROPERTY_FILE, null, $path);
    }

    public function tracking($orderId)
    {
        $lang = $this->sessionStorageService->getLang();
        return $this->fromMemoryCache("tracking.{$orderId}", function() use($orderId, $lang)
        {
            $authHelper = pluginApp(AuthHelper::class);
            $trackingURL = $authHelper->processUnguarded(function() use ($orderId, $lang) {
                $orderRepository = pluginApp(OrderRepositoryContract::class);
                $orderTrackingService = pluginApp(OrderTrackingService::class);

                $order = $orderRepository->findOrderById($orderId);
                return $orderTrackingService->getTrackingURL($order, $lang);
            });

            return $trackingURL;
        });
    }

    private function getShopUrl( $route, $url = null, $routeParams = [], $urlParams = [] )
    {
        $key = $route;

        if(count($routeParams) || count($urlParams))
        {
            $key .= '.'.implode('.', $routeParams) . '.' . json_encode($urlParams);
        }

        return $this->fromMemoryCache($key, function() use ($route, $url, $routeParams, $urlParams)
        {
            $categoryId = RouteConfig::getCategoryId( $route );
            if ( $categoryId > 0 )
            {
                /** @var CategoryService $categoryService */
                $categoryService = pluginApp(CategoryService::class);
                $category = $categoryService->get( $categoryId );

                if ( $category !== null )
                {
                    /** @var CategoryUrlBuilder $categoryUrlBuilder */
                    $categoryUrlBuilder = pluginApp( CategoryUrlBuilder::class );

                    return $this->applyParams(
                        $categoryUrlBuilder->buildUrl( $category->id ),
                        $routeParams,
                        $urlParams
                    );
                }
            }

            return $this->applyParams(
                pluginApp(UrlQuery::class, ['path' => ($url ?? $route)]),
                $routeParams,
                $urlParams
            );
        });
    }

    private function applyParams( $url, $routeParams, $urlParams )
    {
        $routeParam = array_shift($routeParams);
        while(!is_null($routeParam) && strlen($routeParam))
        {
            $url->join($routeParam);
            $routeParam = array_shift($routeParams);
        }

        $queryParameters = http_build_query($urlParams);
        $relativeUrl = $url->toRelativeUrl($this->includeLanguage);

        return $relativeUrl . (strlen($queryParameters) > 0 ? '?' . $queryParameters : '');
    }

    public function equals($routeUrl, $url)
    {
        return $routeUrl === $url || $routeUrl === $url . '/';
    }
}
