<?php //strict
namespace IO\Controllers;

use IO\Extensions\Constants\ShopUrls;
use Plenty\Plugin\Log\Loggable;

/**
 * Class ConfirmationEmailController
 * @package IO\Controllers
 */
class ConfirmationEmailController extends LayoutController
{
    use Loggable;
    /**
     * Prepare and render the data for the order confirmation
     * @return string
     */
    public function showConfirmation($orderAccessKey = '', int $orderId = 0)
    {
        if(strlen($orderAccessKey) && (int)$orderId > 0)
        {
            return $this->urlService->redirectTo(pluginApp(ShopUrls::class)->confirmation . '/'.$orderId.'/'.$orderAccessKey);
        }

        return $this->renderTemplate(
            "tpl.confirmation",
            [
                "data" => ''
            ],
            false
        );
    }
}
