<?php

return [
    'modules' => [
        'products' => [
            'title' => 'Sản phẩm', 'description' => 'Quản lý sản phẩm, trạng thái và hình ảnh.', 'api' => 'products',
            'filters' => [['name' => 'category_ids', 'label' => 'Chọn danh mục để lọc', 'source' => 'categories', 'value' => 'id', 'text' => 'category_name', 'multiple' => true]],
            'columns' => ['first_image' => 'Hình ảnh', 'product_name' => 'Tên sản phẩm', 'slug' => 'Slug', 'sku' => 'SKU', 'category_names' => 'Danh mục', 'is_active' => 'Hoạt động', 'is_featured' => 'Nổi bật'],
            'fields' => [
                ['name' => 'product_name', 'label' => 'Tên sản phẩm', 'type' => 'text', 'required' => true, 'placeholder' => 'Nhập tên sản phẩm'],
                ['name' => 'slug', 'label' => 'Slug sản phẩm', 'type' => 'text', 'placeholder' => 'Nhập slug sản phẩm'],
                ['name' => 'sku', 'label' => 'SKU', 'type' => 'text', 'placeholder' => 'Nhập mã SKU'],
                ['name' => 'description', 'label' => 'Mô tả', 'type' => 'textarea', 'placeholder' => 'Nhập mô tả chi tiết về sản phẩm...'],
                ['name' => 'category_ids', 'label' => 'Danh mục sản phẩm', 'type' => 'multi_select_api', 'source' => 'categories', 'value' => 'id', 'text' => 'category_name', 'placeholder' => '-- Chọn danh mục để thêm --'],
                ['name' => 'is_active', 'label' => 'Đang hoạt động', 'type' => 'checkbox'],
                ['name' => 'is_featured', 'label' => 'Sản phẩm nổi bật', 'type' => 'checkbox'],
                ['name' => 'variant_groups', 'label' => 'Nhóm biến thể', 'type' => 'product_variant_groups', 'source' => 'variant-groups'],
                ['name' => 'images', 'label' => 'Hình ảnh', 'type' => 'files', 'accept' => 'image/jpeg,image/png,image/webp'],
            ],
        ],
        'categories' => [
            'title' => 'Danh mục sản phẩm', 'description' => 'Tổ chức danh mục cho cửa hàng.', 'api' => 'categories',
            'columns' => ['category_name' => 'Tên danh mục', 'description' => 'Mô tả'],
            'fields' => [
                ['name' => 'category_name', 'label' => 'Tên danh mục', 'type' => 'text', 'required' => true],
                ['name' => 'description', 'label' => 'Mô tả', 'type' => 'textarea'],
            ],
        ],
        'variant-groups' => [
            'title' => 'Nhóm biến thể', 'description' => 'Ví dụ: màu sắc, kích thước.', 'api' => 'variant-groups',
            'columns' => ['group_code' => 'Mã nhóm', 'group_name' => 'Tên nhóm'],
            'fields' => [
                ['name' => 'group_code', 'label' => 'Mã nhóm', 'type' => 'text', 'required' => true],
                ['name' => 'group_name', 'label' => 'Tên nhóm', 'type' => 'text', 'required' => true],
            ],
        ],
        'variant-options' => [
            'title' => 'Giá trị biến thể', 'description' => 'Các giá trị thuộc từng nhóm biến thể.', 'api' => 'variant-options',
            'columns' => ['option_code' => 'Mã', 'option_name' => 'Tên', 'product_id' => 'ID sản phẩm', 'variant_group_id' => 'ID nhóm', 'sort_order' => 'Thứ tự', 'is_active' => 'Hoạt động'],
            'fields' => [
                ['name' => 'product_id', 'label' => 'Sản phẩm', 'type' => 'searchable_select_api', 'source' => 'products', 'value' => 'id', 'text' => 'product_name', 'required' => true, 'lock_on_edit' => true, 'placeholder' => 'Tìm theo tên hoặc SKU'],
                ['name' => 'product_variant_group_id', 'label' => 'Nhóm biến thể của sản phẩm', 'type' => 'product_group_select', 'required' => true],
                ['name' => 'option_code', 'label' => 'Mã option', 'type' => 'text', 'required' => true],
                ['name' => 'option_name', 'label' => 'Tên option', 'type' => 'text', 'required' => true],
                ['name' => 'sort_order', 'label' => 'Thứ tự', 'type' => 'number'],
                ['name' => 'is_active', 'label' => 'Hoạt động', 'type' => 'checkbox'],
            ],
        ],
        'product-variants' => [
            'title' => 'Biến thể sản phẩm', 'description' => 'Quản lý SKU, giá, tồn kho và hình ảnh.', 'api' => 'product-variants',
            'filters' => [['name' => 'product_id', 'label' => 'Tất cả sản phẩm', 'source' => 'products', 'value' => 'id', 'text' => 'product_name']],
            'columns' => ['first_image' => 'Hình ảnh', 'sku' => 'SKU', 'product_name' => 'Sản phẩm', 'option_names' => 'Tổ hợp biến thể', 'price' => 'Giá', 'stock' => 'Tồn kho', 'is_active' => 'Hoạt động'],
            'fields' => [
                ['name' => 'product_id', 'label' => 'Sản phẩm', 'type' => 'searchable_select_api', 'source' => 'products', 'value' => 'id', 'text' => 'product_name', 'required' => true, 'lock_on_edit' => true, 'placeholder' => 'Tìm theo tên hoặc SKU'],
                ['name' => 'option_ids', 'label' => 'Giá trị biến thể', 'type' => 'variant_options', 'required' => true],
                ['name' => 'sku', 'label' => 'SKU', 'type' => 'text', 'required' => true, 'placeholder' => 'Nhập mã SKU'],
                ['name' => 'price', 'label' => 'Giá', 'type' => 'number', 'placeholder' => 'Nhập giá bán', 'min' => 0, 'step' => '0.01', 'help' => 'Khi tạo tất cả tổ hợp, giá này được áp dụng giống nhau cho mọi biến thể mới.'],
                ['name' => 'stock', 'label' => 'Tồn kho', 'type' => 'number', 'placeholder' => 'Nhập số lượng tồn kho', 'min' => 0, 'step' => 1, 'help' => 'Khi tạo tất cả tổ hợp, tồn kho này được áp dụng giống nhau cho mọi biến thể mới.'],
                ['name' => 'is_active', 'label' => 'Hoạt động', 'type' => 'checkbox', 'default' => true],
                ['name' => 'images', 'label' => 'Hình ảnh', 'type' => 'files', 'accept' => 'image/jpeg,image/png,image/webp'],
            ],
        ],
        'page-configs' => [
            'title' => 'Cấu hình trang', 'description' => 'Quản lý thông tin chung của công ty và website.', 'api' => 'page-configs',
            'columns' => ['company_name' => 'Tên công ty', 'slogan' => 'Slogan', 'description' => 'Mô tả', 'hotline' => 'Hotline', 'email' => 'Email', 'working_hour' => 'Giờ làm việc', 'addresses' => 'Địa chỉ'],
            'fields' => [
                ['name' => 'company_name', 'label' => 'Tên công ty', 'type' => 'text', 'required' => true, 'placeholder' => 'Nhập tên công ty'],
                ['name' => 'slogan', 'label' => 'Slogan', 'type' => 'text', 'placeholder' => 'Nhập slogan'],
                ['name' => 'description', 'label' => 'Mô tả', 'type' => 'textarea', 'placeholder' => 'Nhập mô tả chung'],
                ['name' => 'hotline', 'label' => 'Hotline', 'type' => 'text', 'placeholder' => 'Nhập số hotline'],
                ['name' => 'email', 'label' => 'Email', 'type' => 'email', 'placeholder' => 'Nhập email liên hệ'],
                ['name' => 'working_hour', 'label' => 'Giờ làm việc', 'type' => 'text', 'placeholder' => 'Nhập giờ làm việc'],
                ['name' => 'favicon', 'label' => 'Favicon', 'type' => 'single_file', 'accept' => 'image/png,image/x-icon,image/jpeg,image/webp'],
                ['name' => 'logo', 'label' => 'Logo', 'type' => 'single_file', 'accept' => 'image/png,image/jpeg,image/webp'],
                ['name' => 'addresses', 'label' => 'Địa chỉ', 'type' => 'repeatable_values', 'placeholder' => 'Nhập địa chỉ', 'add_label' => 'Thêm địa chỉ', 'empty_label' => 'Chưa có địa chỉ nào.'],
                ['name' => 'socials', 'label' => 'Mạng xã hội', 'type' => 'key_value', 'key_placeholder' => 'Tên nền tảng', 'value_placeholder' => 'Nhập URL'],
            ],
        ],
        'page-contents' => [
            'title' => 'Trang nội dung', 'description' => 'Thông tin chung của từng trang.', 'api' => 'page-contents',
            'columns' => ['title' => 'Tên trang', 'slug' => 'Slug'],
            'fields' => [
                ['name' => 'title', 'label' => 'Tên trang', 'type' => 'text', 'required' => true],
                ['name' => 'slug', 'label' => 'Slug', 'type' => 'text', 'required' => true],
            ],
        ],
        'page-sections' => [
            'title' => 'Bố cục / Section', 'description' => 'Sắp xếp các section trong trang.', 'api' => 'page-sections',
            'columns' => ['title' => 'Tiêu đề', 'page_content_id' => 'ID trang', 'subtitle' => 'Phụ đề', 'sort_order' => 'Thứ tự'],
            'fields' => [
                ['name' => 'page_content_id', 'label' => 'ID trang', 'type' => 'number', 'required' => true],
                ['name' => 'title', 'label' => 'Tiêu đề', 'type' => 'text'], ['name' => 'subtitle', 'label' => 'Phụ đề', 'type' => 'text'],
                ['name' => 'content', 'label' => 'Nội dung', 'type' => 'textarea'], ['name' => 'sort_order', 'label' => 'Thứ tự', 'type' => 'number'],
                ['name' => 'files', 'label' => 'Ảnh / video', 'type' => 'files'], ['name' => 'video_urls', 'label' => 'URL video (mỗi dòng một URL)', 'type' => 'lines'],
            ],
        ],
        'section-items' => [
            'title' => 'Nội dung Section', 'description' => 'Quản lý item trong từng section.', 'api' => 'section-items',
            'columns' => ['title' => 'Tiêu đề', 'page_section_id' => 'ID section', 'subtitle' => 'Phụ đề', 'sort_order' => 'Thứ tự'],
            'fields' => [
                ['name' => 'page_section_id', 'label' => 'ID section', 'type' => 'number', 'required' => true],
                ['name' => 'title', 'label' => 'Tiêu đề', 'type' => 'text'], ['name' => 'subtitle', 'label' => 'Phụ đề', 'type' => 'text'],
                ['name' => 'content', 'label' => 'Nội dung', 'type' => 'textarea'], ['name' => 'sort_order', 'label' => 'Thứ tự', 'type' => 'number'],
                ['name' => 'files', 'label' => 'Ảnh / video', 'type' => 'files'], ['name' => 'video_urls', 'label' => 'URL video (mỗi dòng một URL)', 'type' => 'lines'],
            ],
        ],
    ],
];
