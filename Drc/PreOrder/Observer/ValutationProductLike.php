<?php
namespace Drc\PreOrder\Observer;

use Magento\Framework\Event\ObserverInterface;

class ValutationProductLike implements ObserverInterface
{
    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    protected $_messageManager;
    protected $_responseFactory;
    protected $likeFactory;
    protected $likeResource;
    protected $customerSession;


    /**
     * @param \Magento\Framework\Message\ManagerInterface $messageManager
     */
    public function __construct(
        \Drc\PreOrder\Model\LikeFactory $likeFactory,
        \Drc\PreOrder\Model\ResourceModel\Like $likeResource,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Magento\Customer\Model\Session $session,
        \Magento\Framework\App\ResponseFactory $responseFactory
    )
    {
      $this->likeFactory = $likeFactory;
      $this->customerSession = $session;
      $this->likeResource = $likeResource;
      $this->_responseFactory = $responseFactory;
        $this->_messageManager = $messageManager;
    }

    /**
     * add to cart event handler.
     *
     * @param \Magento\Framework\Event\Observer $observer
     *
     * @return $this
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {



      $objectManager = \Magento\Framework\App\ObjectManager::getInstance();

        $product = $observer->getRequest()->getParam("product", null);
        $product_id = $product;
        $product = $objectManager->create("Magento\Catalog\Model\Product")->load($product);
        //checking whether considering a valutation product
	$isValutationProduct = false;
	//retrieving product type
	$type = $product->getTypeId();
	if(is_array($type)) {
        	foreach($type as $it) {
                	if(strcmp($it, "valutation_product") == 0)
                        	$isValutationProduct = true;
        	}
	}
	else {
        	if(strcmp($type, "valutation_product") == 0)
                	$isValutationProduct = true;
	}

        if($isValutationProduct) {
          //checking if user is logged in
          if($this->customerSession->isLoggedIn()) {
            //checking if user already added like for this product
            $customer_id = $this->customerSession->getCustomerId();
            $store_id = $this->customerSession->getCustomer()->getStoreId();
            $item = $this->likeResource->getLikeByProductIdCustomerId($product_id, $customer_id);
            if(!$item) {
              //getting previous likes amount
              $likes = intval($product->getData('valutation_product_likes'));
              //updating likes amount
              $product->addAttributeUpdate("valutation_product_likes", ($likes+1), $product->getStoreId());
              //saving like into database
              $like = $this->likeFactory->create();
              $like->setProductId($product_id);
              $like->setCustomerId($customer_id);
              $like->setStoreId($store_id);
              $like->save();
              //checking if ajax request
              $om = \Magento\Framework\App\ObjectManager::getInstance();
               $request = $om->get('Magento\Framework\App\RequestInterface');

               //setting product parama to null to avoid being added to the cart
               $this->_messageManager->addSuccess("Like aggiunto con successo");
               $observer->getRequest()->setParam('product', false);
                // return $this;
              //if receiving from product category list
                              /* die use for stop excaution */
            }
            else {
              //returning message pointing out user is not logged in
              $this->_messageManager->addError("Hai gia' aggiunto il like per questo prodotto");
              $observer->getRequest()->setParam('product', false);
            }

          }
          else {
            //returning message pointing out user is not logged in
            $this->_messageManager->addError("Non puoi effettuare l'operazione se non sei loggato");
            $observer->getRequest()->setParam('product', false);
          }
	$redirectUrl = $product->getProductUrl();
         // $this->_messageManager->getMessages(true);

        $this->_responseFactory->create()->setRedirect($redirectUrl)->sendResponse();
	exit();

        }
        return $this;
      }
    }

