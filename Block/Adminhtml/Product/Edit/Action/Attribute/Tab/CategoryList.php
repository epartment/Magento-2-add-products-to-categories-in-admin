<?php
namespace Paulmillband\AdminProductsToCategory\Block\Adminhtml\Product\Edit\Action\Attribute\Tab;

use Magento\Catalog\Model\Category as ModelCategory;
use Magento\Store\Model\Group;

class CategoryList extends \Magento\Backend\Block\Widget
{
    protected $categoryHelper;
    protected $categoryFactory;
    protected $storeManager;
    protected $categoryTreeFactory;
    protected $categoryFlatState;
    protected $resultPageFactory;
    protected $categoryCollection;
    protected $context;
    protected $layout;
    protected $dataCollectionFactory;

    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Catalog\Model\ResourceModel\Category\TreeFactory $categoryTreeFactory,
        \Magento\Catalog\Model\Indexer\Category\Flat\State $categoryFlatState,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Magento\Framework\View\LayoutInterface $layout,
        \Magento\Catalog\Helper\Category $categoryHelper,
        \Magento\Catalog\Model\CategoryFactory $categoryFactory,
        \Magento\Framework\Data\CollectionFactory $dataCollectionFactory,
        array $data = []
    ) {
        $this->context = $context;
        $this->categoryHelper = $categoryHelper;
        $this->categoryTreeFactory = $categoryTreeFactory;
        $this->categoryFlatState = $categoryFlatState;
        $this->resultPageFactory = $resultPageFactory;
        $this->layout = $layout;
        $this->storeManager = $context->getStoreManager();
        $this->categoryFactory = $categoryFactory;
        $this->dataCollectionFactory = $dataCollectionFactory;
        parent::__construct(
            $context,
            $data
        );
    }

    /**
     * Retrieve current store categories
     *
     * @param bool|string $sorted
     * @param bool $asCollection
     * @param bool $toLoad
     * @return \Magento\Framework\Data\Tree\Node\Collection or
     * \Magento\Catalog\Model\ResourceModel\Category\Collection or array
     */
    public function getStoreCategories($sorted = false, $asCollection = false, $toLoad = true)
    {
        if ($this->getRequest()->getParam('store')) {
            $parent = $this->storeManager->getStore($this->getRequest()->getParam('store'))->getRootCategoryId();
        } else {
            $parent = $this->storeManager->getStore()->getRootCategoryId();
        }

        $cacheKey = sprintf('%d-%d-%d-%d', $parent, $sorted, $asCollection, $toLoad);
        if (isset($this->_storeCategories[$cacheKey])) {
            return $this->_storeCategories[$cacheKey];
        }

        /**
         * Check if parent node of the store still exists
         */
        $category = $this->categoryFactory->create();
        /* @var $category ModelCategory */
        if (!$category->checkId($parent)) {
            if ($asCollection) {
                return $this->dataCollectionFactory->create();
            }
            return [];
        }

        $recursionLevel = max(
            0,
            (int)$this->_scopeConfig->getValue(
                'catalog/navigation/max_depth',
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE
            )
        );
        $storeCategories = $category->getCategories($parent, $recursionLevel, $sorted, $asCollection, $toLoad);

        $this->_storeCategories[$cacheKey] = $storeCategories;
        return $storeCategories;
    }

    /**
     * @return \Magento\Framework\Data\Tree\Node\Collection or
     * \Magento\Catalog\Model\ResourceModel\Category\Collection
     */
    public function getCategoryCollection()
    {
        if(!$this->categoryCollection){
            $this->categoryCollection = $this->getStoreCategories(true, false, true);
        }
        return $this->categoryCollection;
    }

    /**
     * @param $categoryCollection \Magento\Framework\Data\Tree\Node\Collection
     */
    public function setCategoryCollection($categoryCollection)
    {
        $this->categoryCollection = $categoryCollection;
    }

    /**
     * Return categories helper
     */
    public function getCategoryHelper()
    {
        return $this->categoryHelper;
    }

    /**
     * @param $category \Magento\Framework\Data\Tree\Node\Collection or
     * \Magento\Catalog\Model\ResourceModel\Category\Collection
     * @return array
     */
    public function getChildCategories($category)
    {
        if ($this->categoryFlatState->isFlatEnabled() && $category->getUseFlatResource()) {
            $subcategories = (array)$category->getChildrenNodes();
        } else {
            $subcategories = $category->getChildren();
        }
        return $subcategories;
    }

    /**
     * @param $categories \Magento\Framework\Data\Tree\Node\Collection or
     * \Magento\Catalog\Model\ResourceModel\Category\Collection
     * @return mixed
     */
    public function getSubBlockHtml($categories){
        $class = get_class($this);
        $template = $this->getTemplate();
        $block = $this->layout
            ->createBlock($class)
            ->setTemplate($template);
        $block->setCategoryCollection($categories);
        $html = $block->toHtml();
        return $html;
    }

    /**
     * @param Group $group
     * @return array
     */
    public function getStoreCollection(Group $group)
    {
        return $group->getStores();
    }
}

