<?php //strict

namespace IO\Api\Resources;

use IO\Services\VdiSearch\SearchPresets\CategoryItems;
use IO\Services\VdiSearch\SearchPresets\Facets;
use IO\Services\VdiSearch\SearchPresets\SearchItems;
use IO\Contracts\ItemSearchContract;
use Plenty\Plugin\Http\Response;
use Plenty\Plugin\Http\Request;
use IO\Api\ApiResource;
use IO\Api\ApiResponse;
use IO\Api\ResponseCode;

/**
 * Class FacetResource
 * @package IO\Api\Resources
 */
class FacetResource extends ApiResource
{
    /**
     * FacetResource constructor.
     * @param Request $request
     * @param ApiResponse $response
     */
    public function __construct(Request $request, ApiResponse $response)
    {
        parent::__construct($request, $response);
    }

    /**
     * Get Facets
     * @return Response
     */
    public function index():Response
    {
        $categoryId   = (int)$this->request->get('categoryId', 0);
        $searchString = $this->request->get('query', '');

        if($categoryId > 0 || strlen($searchString))
        {
            $itemListOptions = [
                'page'         => 1,
                'itemsPerPage' => 0,
                'sorting'      => '',
                'facets'       => $this->request->get('facets', '' ),
                'priceMin'     => $this->request->get('priceMin', 0),
                'priceMax'     => $this->request->get('priceMax', 0)
            ];

            if($categoryId > 0)
            {
                $itemListOptions['categoryId'] = $categoryId;
            }
            else
            {
                $itemListOptions['query'] = $searchString;
            }

            $searchParams = [
                'facets'   => Facets::getSearchFactory( $itemListOptions ),
                'itemList' => $categoryId > 0 ? CategoryItems::getSearchFactory( $itemListOptions ) : SearchItems::getSearchFactory( $itemListOptions )
            ];

            /** @var ItemSearchContract $itemSearchService */
            $itemSearchService = pluginApp( ItemSearchContract::class );
            $response = $itemSearchService->getResults($searchParams);

            return $this->response->create($response, ResponseCode::OK);
        }
        else
        {
            return $this->response->create( null, ResponseCode::BAD_REQUEST );
        }
    }
}
