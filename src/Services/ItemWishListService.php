<?php
/**
 * Created by IntelliJ IDEA.
 * User: ihussein
 * Date: 01.08.17
 */

namespace IO\Services;

use IO\Repositories\ItemWishListRepository;
use IO\Repositories\ItemWishListGuestRepository;
use Plenty\Modules\Webshop\Contracts\ContactRepositoryContract;
use Plenty\Modules\Webshop\Contracts\SessionStorageRepositoryContract;

/**
 * Class WishListService
 * @package IO\Services
 */
class ItemWishListService
{
    private $itemWishListRepo;

    public function __construct(SessionStorageRepositoryContract $sessionStorageRepositoryContract)
    {
        if ($sessionStorageRepositoryContract->getSessionValue(
            SessionStorageRepositoryContract::GUEST_WISHLIST_MIGRATION
        )) {
            $this->migrateGuestItemWishList();
            $sessionStorageRepositoryContract->setSessionValue(
                SessionStorageRepositoryContract::GUEST_WISHLIST_MIGRATION,
                false
            );
        }

        /** @var ContactRepositoryContract $contactRepository */
        $contactRepository = pluginApp(ContactRepositoryContract::class);

        if ((int)$contactRepository->getContactId() > 0) {
            $itemWishListRepo = pluginApp(ItemWishListRepository::class);
        } else {
            $itemWishListRepo = pluginApp(ItemWishListGuestRepository::class);
        }

        $this->itemWishListRepo = $itemWishListRepo;
    }

    /**
     * @param int $variationId
     * @param int $quantity
     * @return mixed
     */
    public function addItemWishListEntry(int $variationId, int $quantity)
    {
        return $this->itemWishListRepo->addItemWishListEntry($variationId, $quantity);
    }

    /**
     * @param int $variationId
     * @return bool
     */
    public function isItemInWishList(int $variationId)
    {
        return $this->itemWishListRepo->isItemInWishList($variationId);
    }

    /**
     * @return array
     */
    public function getItemWishList()
    {
        return $this->itemWishListRepo->getItemWishList();
    }

    /**
     * @return int
     */
    public function getCountedItemWishList()
    {
        return $this->itemWishListRepo->getCountedItemWishList();
    }

    /**
     * @param int $variationId
     * @return bool
     */
    public function removeItemWishListEntry(int $variationId)
    {
        return $this->itemWishListRepo->removeItemWishListEntry($variationId);
    }

    public function migrateGuestItemWishList()
    {
        /**
         * @var ItemWishListGuestRepository $guestWishListRepo
         */
        $guestWishListRepo = pluginApp(ItemWishListGuestRepository::class);

        $guestWishList = $guestWishListRepo->getItemWishList();

        if (count($guestWishList)) {
            /**
             * @var ItemWishListRepository $contactWishListRepo
             */
            $contactWishListRepo = pluginApp(ItemWishListRepository::class);

            foreach ($guestWishList as $variationId) {
                if ((int)$variationId > 0) {
                    $contactWishListRepo->addItemWishListEntry($variationId);
                }
            }

            $guestWishListRepo->resetItemWishList();
        }
    }
}
