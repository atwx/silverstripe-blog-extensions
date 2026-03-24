<?php

namespace ATWX\BlogExtensions\Admins;

use SilverStripe\Admin\ModelAdmin;
use SilverStripe\Blog\Model\BlogPost;

class BlogAdmin extends ModelAdmin
{
    private static $url_segment = 'blog';

    private static $menu_title = 'Blog';

    private static $managed_models = [
        BlogPost::class,
    ];
}
