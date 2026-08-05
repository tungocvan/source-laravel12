<?php

return array (
  'name' => 'Website',
  'type' => 'domain',
  'enabled' => false,
  'depends' => ['User', 'Product', 'Category', 'Post', 'Order'],
  'permissions' => 
  array (
    0 => 'view_website',
    1 => 'create_website',
    2 => 'edit_website',
    3 => 'delete_website',
  ),
);
