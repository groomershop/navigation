<?php

namespace MageSuite\Navigation\Setup;

class UpgradeData implements \Magento\Framework\Setup\UpgradeDataInterface
{
    /**
     * @var \Magento\Eav\Setup\EavSetupFactory
     */
    private $eavSetupFactory;

    /**
     * @var \Magento\Framework\Setup\ModuleDataSetupInterface
     */
    protected $moduleDataSetupInterface;

    /**
     * @var \Magento\Eav\Setup\EavSetup
     */
    protected $eavSetup;

    /**
     * @var \Magento\Eav\Model\Config
     */
    private $eavConfig;

    /**
     * @var \MageSuite\Navigation\Migration\AddIncludeInMobileDefaultValue
     */
    protected $addIncludeInMobileDefaultValue;

    public function __construct(
        \Magento\Eav\Setup\EavSetupFactory $eavSetupFactory,
        \Magento\Framework\Setup\ModuleDataSetupInterface $moduleDataSetupInterface,
        \Magento\Eav\Model\Config $eavConfig,
        \MageSuite\Navigation\Migration\AddIncludeInMobileDefaultValue $addIncludeInMobileDefaultValue
    ) {
        $this->eavSetupFactory = $eavSetupFactory;
        $this->moduleDataSetupInterface = $moduleDataSetupInterface;
        $this->eavSetup = $this->eavSetupFactory->create(['setup' => $this->moduleDataSetupInterface]);
        $this->eavConfig = $eavConfig;
        $this->addIncludeInMobileDefaultValue = $addIncludeInMobileDefaultValue;
    }

    public function upgrade(
        \Magento\Framework\Setup\ModuleDataSetupInterface $setup,
        \Magento\Framework\Setup\ModuleContextInterface $context
    ) {
        $setup->startSetup();

        if (version_compare($context->getVersion(), '1.0.1', '<')) {
            $this->addIncludeInMobileNavigationAttribute();
        }

        if (version_compare($context->getVersion(), '1.0.2', '<')) {
            $this->addDefaultValueToIncludeInMobileNavAttribute();
        }

        if (version_compare($context->getVersion(), '1.0.3', '<')) {
            $this->migrateNavigationImageTeaserToNewVersion();
        }

        $setup->endSetup();
    }

    protected function addIncludeInMobileNavigationAttribute()
    {
        if (!$this->eavSetup->getAttributeId(\Magento\Catalog\Model\Category::ENTITY, 'include_in_mobile_navigation')) {
            $this->eavSetup->addAttribute(
                \Magento\Catalog\Model\Category::ENTITY,
                'include_in_mobile_navigation',
                [
                    'type' => 'int',
                    'label' => 'Include in mobile navigation',
                    'input' => 'select',
                    'source' => \Magento\Eav\Model\Entity\Attribute\Source\Boolean::class,
                    'default' => '1',
                    'sort_order' => 10,
                    'global' => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_STORE,
                    'group' => 'General Information',
                ]
            );
        }
    }

    protected function addDefaultValueToIncludeInMobileNavAttribute()
    {
        $includeInMobileAttributeId = $this->eavSetup->getAttributeId(\Magento\Catalog\Model\Category::ENTITY, 'include_in_mobile_navigation');
        $includeInDesktopAttributeId = $this->eavSetup->getAttributeId(\Magento\Catalog\Model\Category::ENTITY, 'include_in_menu');

        if (!$includeInMobileAttributeId or !$includeInDesktopAttributeId) {
            return false;
        }

        $this->addIncludeInMobileDefaultValue->execute($includeInMobileAttributeId, $includeInDesktopAttributeId);
    }

    protected function migrateNavigationImageTeaserToNewVersion()
    {
        $entityType = $this->eavSetup->getEntityTypeId('catalog_category');

        $this->eavSetup->updateAttribute($entityType, 'image_teaser_headline', 'frontend_label', 'Slogan');
        $this->eavSetup->updateAttribute($entityType, 'image_teaser_headline', 'attribute_code', 'image_teaser_slogan');

        $this->eavSetup->removeAttribute(\Magento\Catalog\Model\Category::ENTITY, 'image_teaser_subheadline');

        $this->eavSetup->updateAttribute($entityType, 'image_teaser_paragraph', 'frontend_label', 'Description');
        $this->eavSetup->updateAttribute($entityType, 'image_teaser_paragraph', 'attribute_code', 'image_teaser_description');

        $this->eavSetup->updateAttribute($entityType, 'image_teaser_button_label', 'frontend_label', 'CTA Label');
        $this->eavSetup->updateAttribute($entityType, 'image_teaser_button_label', 'attribute_code', 'image_teaser_cta_label');

        $this->eavSetup->updateAttribute($entityType, 'image_teaser_button_link', 'frontend_label', 'CTA target link');
        $this->eavSetup->updateAttribute($entityType, 'image_teaser_button_link', 'attribute_code', 'image_teaser_cta_link');
    }
}
