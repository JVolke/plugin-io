<?php

namespace IO\Controllers;

use IO\Api\ResponseCode;
use IO\Constants\SessionStorageKeys;
use IO\Middlewares\Middleware;
use IO\Services\CustomerService;
use IO\Services\SessionStorageService;
use Plenty\Modules\Authorization\Services\AuthHelper;
use Plenty\Modules\Cloud\Storage\Models\StorageObject;
use Plenty\Modules\Document\Contracts\DocumentRepositoryContract;
use Plenty\Modules\Document\Models\Document;
use Plenty\Modules\Order\Contracts\OrderRepositoryContract;
use Plenty\Plugin\Http\Request;
use Plenty\Plugin\Http\Response;
use Plenty\Modules\Order\Models\Order;

/**
 * Class DocumentController
 * @package IO\Controllers
 */
class DocumentController extends LayoutController
{
    /**
     * @param int $documentId
     * @param Response $response
     * @return Response
     */
    public function preview($documentId, Response $response)
    {
        /** @var Request $request */
        $request = pluginApp(Request::class);
        
        $requestOrderId = $request->get('orderId', 0);
        $requestAccessKey = $request->get('accessKey', '');
        
        if($requestOrderId <= 0 || !strlen($requestAccessKey))
        {
            /** @var SessionStorageService $sessionStorageService */
            $sessionStorageService = pluginApp(SessionStorageService::class);
            $sessionOrder = $sessionStorageService->getSessionValue(SessionStorageKeys::LAST_ACCESSED_ORDER);
            
            $requestOrderId = $sessionOrder['orderId'];
            $requestAccessKey = $sessionOrder['accessKey'];
        }
        
        /** @var CustomerService $customerService */
        $customerService = pluginApp(CustomerService::class);
        
        /** @var DocumentRepositoryContract $documentRepo */
        $documentRepo = pluginApp(DocumentRepositoryContract::class);
        $document = $documentRepo->findById($documentId);
        
        $documentStorageObject = null;
        
        if($document instanceof Document)
        {
            /** @var Order $order */
            $order = $document->orders->first();
            
            if($order instanceof Order)
            {
                $orderContactId = $order->relations->where('referenceType', 'contact')->first()->referenceId;
                
                if((int)$orderContactId > 0 && $orderContactId == $customerService->getContactId())
                {
                    //document is matching with the logged in contact
                    $documentStorageObject = $documentRepo->getDocumentStorageObject($document->path);
                }
                elseif(!is_null($requestOrderId)
                    && !is_null($requestAccessKey)
                    && (int)$requestOrderId > 0
                    && strlen($requestAccessKey))
                {
                    /** @var AuthHelper $authHelper */
                    $authHelper = pluginApp(AuthHelper::class);
                    
                    /** @var OrderRepositoryContract $orderRepo */
                    $orderRepo = pluginApp(OrderRepositoryContract::class);
                    $orderAccessKey = $authHelper->processUnguarded(function() use ($order, $orderRepo) {
                        return $orderRepo->generateAccessKey($order->id);
                    });
                    
                    if($requestOrderId == $order->id && $requestAccessKey == $orderAccessKey)
                    {
                        //orderId and accessKey are matching with the documents order
                        $documentStorageObject = $documentRepo->getDocumentStorageObject($document->path);
                    }
                }
            }
        }
        
        if($documentStorageObject instanceof StorageObject)
        {
            $response = $response->make(
                $documentStorageObject->body,
                200,
                [
                    'Content-Type' => $documentStorageObject->contentType,
                    'Content-Disposition' => 'inline; filename="' . $document->path . '";',
                    'Content-Length' => $documentStorageObject->contentLength,
                ]
            );
        }
        else
        {
            //document not found or logged in contact not matching
            $response->forceStatus(ResponseCode::NOT_FOUND);
            Middleware::$FORCE_404 = true;
        }
        
        return $response;
    }
}
