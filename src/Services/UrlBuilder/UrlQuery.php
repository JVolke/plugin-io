<?php

namespace IO\Services\UrlBuilder;

use IO\Helper\Utils;
use IO\Services\WebstoreConfigurationService;
use Plenty\Plugin\ConfigRepository;

class UrlQuery
{
    private $domain;
    private $path;
    private $lang;

    public static function shouldAppendTrailingSlash()
    {
        /** @var ConfigRepository $configRepository */
        $configRepository = pluginApp(ConfigRepository::class);
        return $configRepository->get('plenty.system.info.urlTrailingSlash', 0) === 2;
    }

    public function __construct( $path = null, $lang = null )
    {
        /** @var WebstoreConfigurationService $webstoreConfigService */
        $webstoreConfigService = pluginApp(WebstoreConfigurationService::class);
        $this->domain = $webstoreConfigService->getWebstoreConfig()->domainSsl;
        $this->path = $path;

        if ( $path !== null )
        {
            if (substr($this->path, 0, 1) !== "/") {
                $this->path = "/" . $this->path;
            }

            if (substr($this->path, strlen($this->path) - 1, 1) === "/") {
                $this->path = substr($this->path, 0, strlen($this->path) - 1);
            }
        }


        if ( $lang === null )
        {
            $this->lang = Utils::getLang();
        }
        else
        {
            $this->lang = $lang;
        }
    }

    public function append( $suffix ): UrlQuery
    {
        $this->path = $this->path . $suffix;

        return $this;
    }

    public function join( $path ): UrlQuery
    {
        if ( substr( $path, 0, 1 ) !== "/" && substr( $this->path, strlen($this->path)-1, 1 ) !== "/" )
        {
            $path = "/" . $path;
        }

        if ( substr( $path, strlen($path)-1, 1 ) === "/" )
        {
            $path = substr( $path, 0, strlen( $path ) - 1 );
        }

        return $this->append( $path );
    }

    public function toAbsoluteUrl( bool $includeLanguage = false )
    {
        if ( $this->path === null )
        {
            return null;
        }

        return $this->domain . $this->toRelativeUrl( $includeLanguage );
    }

    public function toRelativeUrl( bool $includeLanguage = false )
    {
        if ( $this->path === null )
        {
            return null;
        }
    
        $splittedPath  = explode('?', $this->path);
        $path = $splittedPath[0];
    
        $queryParams = '';
        if (isset($splittedPath[1]))
        {
            $queryParams = $splittedPath[1];
        }
        
        if (isset($path[strlen($path)-1]) && $path[strlen($path)-1] == '/')
        {
            $path = substr($path, 0, -1);
        }

        $queryParams = strlen($queryParams) ? "?" . $queryParams : "";

        $trailingSlash = self::shouldAppendTrailingSlash() ? "/" : "";

        if ( $includeLanguage && strpos($path, '/'.$this->lang) !== 0)
        {
            return '/' . $this->lang . $path . $trailingSlash . $queryParams;
        }
        elseif (strlen($path) == 0)
        {
            return '/';
        }

        return $path . $trailingSlash . $queryParams;
    }

    public function getPath( bool $includeLanguage = false )
    {
        if ( $this->path === null )
        {
            return null;
        }

        return substr( $this->toRelativeUrl( $includeLanguage ), 1 );
    }

    public function equals( $path )
    {
        return $this->path === $path || $this->path === $path."/";
    }
}
