<?php

namespace Terminal42\UrlRewriteBundle\Test\Fixtures;

class InsertTags
{
    public function replace($buffer)
    {
        return str_replace('{{link_url::1}}', 'page.html', $buffer);
    }
}
