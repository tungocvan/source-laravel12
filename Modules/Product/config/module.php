<?php

return array (
  'name' => 'Product',
  'type' => 'domain',
  'enabled' => false,
  'depends' => 
  array (
    0 => 'User',
    1 => 'Category',
  ),
  'permissions' => 
  array (
    0 => 'view_product',
    1 => 'create_product',
    2 => 'edit_product',
    3 => 'delete_product',
  ),
);
