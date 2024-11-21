// frontend/admin/js/admin.js

document.addEventListener('DOMContentLoaded', () => {
    // === Category Management ===
    const addCategoryForm = document.getElementById('add-category-form');
    const categoryMessage = document.getElementById('category-message');
    const categoriesTableBody = document.querySelector('#categories-table tbody');

    // Edit Category Modal Elements
    const editCategoryModal = document.getElementById('editCategoryModal');
    const closeCategoryModalBtn = document.getElementById('closeCategoryModal');
    const editCategoryForm = document.getElementById('edit-category-form');

    // === Banner Management ===
    const addBannerForm = document.getElementById('add-banner-form');
    const bannerMessage = document.getElementById('banner-message');
    const bannersTableBody = document.querySelector('#banners-table tbody');

    // Edit Banner Modal Elements
    const editBannerModal = document.getElementById('editBannerModal');
    const closeBannerModalBtn = document.getElementById('closeBannerModal');
    const editBannerForm = document.getElementById('edit-banner-form');

    // === Product Management ===
    const addProductForm = document.getElementById('add-product-form');
    const productMessage = document.getElementById('product-message');
    const productsTableBody = document.querySelector('#products-table tbody');
    const productCategorySelect = document.getElementById('product_category');
    const editProductCategorySelect = document.getElementById('edit_product_category');

    // Edit Product Modal Elements
    const editProductModal = document.getElementById('editProductModal');
    const closeProductModalBtn = document.getElementById('closeProductModal');
    const editProductForm = document.getElementById('edit-product-form');

    // Utility function to escape HTML
    function escapeHtml(text) {
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.replace(/[&<>"']/g, function(m) { return map[m]; });
    }

    // === Fetch and Populate Categories ===
    async function fetchCategories() {
        try {
            const response = await fetch('http://localhost:8000/admin/manage_fe.php', { // URL Backend yang Benar
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'get_categories'
                })
            });
            const data = await response.json();
            if (data.success) {
                populateCategoriesTable(data.data);
                populateCategorySelect(data.data);
            } else {
                categoryMessage.innerHTML = `<div class="message error">${data.message}</div>`;
            }
        } catch (error) {
            console.error('Error fetching categories:', error);
            categoryMessage.innerHTML = `<div class="message error">Terjadi kesalahan saat mengambil kategori.</div>`;
        }
    }

    function populateCategoriesTable(categories) {
        categoriesTableBody.innerHTML = '';
        categories.forEach(category => {
            const row = document.createElement('tr');
            row.innerHTML = `
                <td>${category.id}</td>
                <td>${escapeHtml(category.name)}</td>
                <td>${escapeHtml(category.description)}</td>
                <td>
                    <button class="btn-edit" onclick="openEditCategoryModal(${category.id}, '${escapeHtml(category.name)}', '${escapeHtml(category.description)}')">Edit</button>
                    <button class="btn-delete" onclick="deleteCategory(${category.id})">Delete</button>
                </td>
            `;
            categoriesTableBody.appendChild(row);
        });
    }

    function populateCategorySelect(categories) {
        productCategorySelect.innerHTML = '<option value="">Pilih Kategori</option>';
        editProductCategorySelect.innerHTML = '<option value="">Pilih Kategori</option>';
        categories.forEach(category => {
            const option = document.createElement('option');
            option.value = category.id;
            option.textContent = category.name;
            productCategorySelect.appendChild(option.cloneNode(true));
            editProductCategorySelect.appendChild(option.cloneNode(true));
        });
    }

    // === Add Category ===
    addCategoryForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const formData = new FormData(addCategoryForm);
        formData.append('action', 'add_category');

        try {
            const response = await fetch('http://localhost:8000/admin/manage_fe.php', { // URL Backend yang Benar
                method: 'POST',
                body: formData
            });
            const data = await response.json();
            if (data.success) {
                categoryMessage.innerHTML = `<div class="message success">${data.message}</div>`;
                addCategoryForm.reset();
                fetchCategories();
            } else {
                categoryMessage.innerHTML = `<div class="message error">${data.message}</div>`;
            }
        } catch (error) {
            console.error('Error adding category:', error);
            categoryMessage.innerHTML = `<div class="message error">Terjadi kesalahan saat menambahkan kategori.</div>`;
        }
    });

    // === Delete Category ===
    window.deleteCategory = async (id) => {
        if (confirm('Apakah Anda yakin ingin menghapus kategori ini?')) {
            const formData = new FormData();
            formData.append('action', 'delete_category');
            formData.append('category_id', id);

            try {
                const response = await fetch('http://localhost:8000/admin/manage_fe.php', { // URL Backend yang Benar
                    method: 'POST',
                    body: formData
                });
                const data = await response.json();
                if (data.success) {
                    categoryMessage.innerHTML = `<div class="message success">${data.message}</div>`;
                    fetchCategories();
                } else {
                    categoryMessage.innerHTML = `<div class="message error">${data.message}</div>`;
                }
            } catch (error) {
                console.error('Error deleting category:', error);
                categoryMessage.innerHTML = `<div class="message error">Terjadi kesalahan saat menghapus kategori.</div>`;
            }
        }
    };

    // === Edit Category ===
    window.openEditCategoryModal = (id, name, description) => {
        document.getElementById('edit_category_id').value = id;
        document.getElementById('edit_category_name').value = name;
        document.getElementById('edit_category_description').value = description;
        editCategoryModal.style.display = 'block';
    };

    closeCategoryModalBtn.addEventListener('click', () => {
        editCategoryModal.style.display = 'none';
    });

    editCategoryForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const formData = new FormData(editCategoryForm);
        formData.append('action', 'edit_category');

        try {
            const response = await fetch('http://localhost:8000/admin/manage_fe.php', { // URL Backend yang Benar
                method: 'POST',
                body: formData
            });
            const data = await response.json();
            if (data.success) {
                categoryMessage.innerHTML = `<div class="message success">${data.message}</div>`;
                editCategoryForm.reset();
                editCategoryModal.style.display = 'none';
                fetchCategories();
            } else {
                categoryMessage.innerHTML = `<div class="message error">${data.message}</div>`;
            }
        } catch (error) {
            console.error('Error editing category:', error);
            categoryMessage.innerHTML = `<div class="message error">Terjadi kesalahan saat mengedit kategori.</div>`;
        }
    });

    // === Banner Management ===
    async function fetchBanners() {
        try {
            const response = await fetch('http://localhost:8000/admin/manage_fe.php', { // URL Backend yang Benar
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'get_banners'
                })
            });
            const data = await response.json();
            if (data.success) {
                populateBannersTable(data.data);
            } else {
                bannerMessage.innerHTML = `<div class="message error">${data.message}</div>`;
            }
        } catch (error) {
            console.error('Error fetching banners:', error);
            bannerMessage.innerHTML = `<div class="message error">Terjadi kesalahan saat mengambil banner.</div>`;
        }
    }

    function populateBannersTable(banners) {
        bannersTableBody.innerHTML = '';
        banners.forEach(banner => {
            const row = document.createElement('tr');
            row.innerHTML = `
                <td>${banner.id}</td>
                <td><img src="http://localhost:8000/${banner.image_path}" alt="${escapeHtml(banner.alt_text)}" width="100"></td>
                <td>${escapeHtml(banner.alt_text)}</td>
                <td>${banner.active ? 'Ya' : 'Tidak'}</td>
                <td>
                    <button class="btn-edit" onclick="openEditBannerModal(${banner.id}, '${escapeHtml(banner.alt_text)}', ${banner.active})">Edit</button>
                    <button class="btn-delete" onclick="deleteBanner(${banner.id})">Delete</button>
                </td>
            `;
            bannersTableBody.appendChild(row);
        });
    }

    // === Add Banner ===
    addBannerForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const formData = new FormData(addBannerForm);
        formData.append('action', 'add_banner');

        try {
            const response = await fetch('http://localhost:8000/admin/manage_fe.php', { // URL Backend yang Benar
                method: 'POST',
                body: formData
            });
            const data = await response.json();
            if (data.success) {
                bannerMessage.innerHTML = `<div class="message success">${data.message}</div>`;
                addBannerForm.reset();
                fetchBanners();
            } else {
                bannerMessage.innerHTML = `<div class="message error">${data.message}</div>`;
            }
        } catch (error) {
            console.error('Error adding banner:', error);
            bannerMessage.innerHTML = `<div class="message error">Terjadi kesalahan saat menambahkan banner.</div>`;
        }
    });

    // === Delete Banner ===
    window.deleteBanner = async (id) => {
        if (confirm('Apakah Anda yakin ingin menghapus banner ini?')) {
            const formData = new FormData();
            formData.append('action', 'delete_banner');
            formData.append('banner_id', id);

            try {
                const response = await fetch('http://localhost:8000/admin/manage_fe.php', { // URL Backend yang Benar
                    method: 'POST',
                    body: formData
                });
                const data = await response.json();
                if (data.success) {
                    bannerMessage.innerHTML = `<div class="message success">${data.message}</div>`;
                    fetchBanners();
                } else {
                    bannerMessage.innerHTML = `<div class="message error">${data.message}</div>`;
                }
            } catch (error) {
                console.error('Error deleting banner:', error);
                bannerMessage.innerHTML = `<div class="message error">Terjadi kesalahan saat menghapus banner.</div>`;
            }
        }
    };

    // === Edit Banner ===
    window.openEditBannerModal = (id, altText, active) => {
        document.getElementById('edit_banner_id').value = id;
        document.getElementById('edit_banner_alt_text').value = altText;
        document.getElementById('edit_banner_active').checked = active === 1;
        editBannerModal.style.display = 'block';
    };

    closeBannerModalBtn.addEventListener('click', () => {
        editBannerModal.style.display = 'none';
    });

    editBannerForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const formData = new FormData(editBannerForm);
        formData.append('action', 'edit_banner');

        try {
            const response = await fetch('http://localhost:8000/admin/manage_fe.php', { // URL Backend yang Benar
                method: 'POST',
                body: formData
            });
            const data = await response.json();
            if (data.success) {
                bannerMessage.innerHTML = `<div class="message success">${data.message}</div>`;
                editBannerForm.reset();
                editBannerModal.style.display = 'none';
                fetchBanners();
            } else {
                bannerMessage.innerHTML = `<div class="message error">${data.message}</div>`;
            }
        } catch (error) {
            console.error('Error editing banner:', error);
            bannerMessage.innerHTML = `<div class="message error">Terjadi kesalahan saat mengedit banner.</div>`;
        }
    });

    // === Product Management ===
    async function fetchProducts() {
        try {
            const response = await fetch('http://localhost:8000/admin/manage_fe.php', { // URL Backend yang Benar
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'get_products'
                })
            });
            const data = await response.json();
            if (data.success) {
                populateProductsTable(data.data);
            } else {
                productMessage.innerHTML = `<div class="message error">${data.message}</div>`;
            }
        } catch (error) {
            console.error('Error fetching products:', error);
            productMessage.innerHTML = `<div class="message error">Terjadi kesalahan saat mengambil produk.</div>`;
        }
    }

    function populateProductsTable(products) {
        productsTableBody.innerHTML = '';
        products.forEach(product => {
            const row = document.createElement('tr');
            row.innerHTML = `
                <td>${product.id}</td>
                <td><img src="http://localhost:8000/${product.image_path}" alt="${escapeHtml(product.name)}" width="100"></td>
                <td>${escapeHtml(product.name)}</td>
                <td>${escapeHtml(product.description)}</td>
                <td>${Number(product.price).toLocaleString('id-ID')}</td>
                <td>${escapeHtml(product.category_name)}</td>
                <td>
                    <button class="btn-edit" onclick="openEditProductModal(${product.id}, '${escapeHtml(product.name)}', '${escapeHtml(product.description)}', '${product.price}', ${product.category_id})">Edit</button>
                    <button class="btn-delete" onclick="deleteProduct(${product.id})">Delete</button>
                </td>
            `;
            productsTableBody.appendChild(row);
        });
    }

    // === Add Product ===
    addProductForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const formData = new FormData(addProductForm);
        formData.append('action', 'add_product');

        try {
            const response = await fetch('http://localhost:8000/admin/manage_fe.php', { // URL Backend yang Benar
                method: 'POST',
                body: formData
            });
            const data = await response.json();
            if (data.success) {
                productMessage.innerHTML = `<div class="message success">${data.message}</div>`;
                addProductForm.reset();
                fetchProducts();
            } else {
                productMessage.innerHTML = `<div class="message error">${data.message}</div>`;
            }
        } catch (error) {
            console.error('Error adding product:', error);
            productMessage.innerHTML = `<div class="message error">Terjadi kesalahan saat menambahkan produk.</div>`;
        }
    });

    // === Delete Product ===
    window.deleteProduct = async (id) => {
        if (confirm('Apakah Anda yakin ingin menghapus produk ini?')) {
            const formData = new FormData();
            formData.append('action', 'delete_product');
            formData.append('product_id', id);

            try {
                const response = await fetch('http://localhost:8000/admin/manage_fe.php', { // URL Backend yang Benar
                    method: 'POST',
                    body: formData
                });
                const data = await response.json();
                if (data.success) {
                    productMessage.innerHTML = `<div class="message success">${data.message}</div>`;
                    fetchProducts();
                } else {
                    productMessage.innerHTML = `<div class="message error">${data.message}</div>`;
                }
            } catch (error) {
                console.error('Error deleting product:', error);
                productMessage.innerHTML = `<div class="message error">Terjadi kesalahan saat menghapus produk.</div>`;
            }
        }
    };

    // === Edit Product ===
    window.openEditProductModal = (id, name, description, price, category_id) => {
        document.getElementById('edit_product_id').value = id;
        document.getElementById('edit_product_name').value = name;
        document.getElementById('edit_product_description').value = description;
        document.getElementById('edit_product_price').value = Number(price).toLocaleString('id-ID');
        document.getElementById('edit_product_category').value = category_id;
        editProductModal.style.display = 'block';
    };

    closeProductModalBtn.addEventListener('click', () => {
        editProductModal.style.display = 'none';
    });

    editProductForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const formData = new FormData(editProductForm);
        formData.append('action', 'edit_product');

        try {
            const response = await fetch('http://localhost:8000/admin/manage_fe.php', { // URL Backend yang Benar
                method: 'POST',
                body: formData
            });
            const data = await response.json();
            if (data.success) {
                productMessage.innerHTML = `<div class="message success">${data.message}</div>`;
                editProductForm.reset();
                editProductModal.style.display = 'none';
                fetchProducts();
            } else {
                productMessage.innerHTML = `<div class="message error">${data.message}</div>`;
            }
        } catch (error) {
            console.error('Error editing product:', error);
            productMessage.innerHTML = `<div class="message error">Terjadi kesalahan saat mengedit produk.</div>`;
        }
    });

    // === Initial Fetch ===
    fetchCategories();
    fetchBanners();
    fetchProducts();
});

// === Close Modals When Clicking Outside ===
window.onclick = function(event) {
    const editCategoryModal = document.getElementById('editCategoryModal');
    const editBannerModal = document.getElementById('editBannerModal');
    const editProductModal = document.getElementById('editProductModal');

    if (event.target == editCategoryModal) {
        editCategoryModal.style.display = 'none';
    }

    if (event.target == editBannerModal) {
        editBannerModal.style.display = 'none';
    }

    if (event.target == editProductModal) {
        editProductModal.style.display = 'none';
    }
};
