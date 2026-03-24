<?php

namespace ATWX\BlogExtensions;

use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Injector\Injectable;

/**
 * Helper class for managing configuration settings for the SilverStripe Blog Extensions module.
 */
class BlogExtensionSettings
{
    use Configurable;
    use Injectable;
    
    private static $overhauled_summary_fields = true;
    
    private static $enable_preview_button = true;
    
    private static $auto_set_parent_blog = true;

    private static $enable_blog_modeladmin = true;

    private static $disable_splitview_editing = true;

    private static $enable_expiredates = true;

    private static $enable_category_dropdown = true;
}
