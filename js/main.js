// frontend/js/main.js

/* 
 * Fungsi untuk memuat komponen HTML secara asinkron.
 * Mengambil konten dari URL yang diberikan dan menyisipkannya ke dalam elemen dengan ID yang ditentukan.
 */
async function loadComponent(url, elementId) {
    try {
        const response = await fetch(url);
        if (!response.ok) throw new Error(`Gagal memuat ${url}`);
        const content = await response.text();
        document.getElementById(elementId).innerHTML = content;
    } catch (error) {
        console.error(error);
    }
}

/* 
 * Variabel untuk menyimpan kategori yang dipilih oleh pengguna.
 * Digunakan untuk menyaring produk berdasarkan kategori.
 */
let selectedCategory = null;

/* 
 * Fungsi utilitas untuk mengkapitalkan huruf pertama dari sebuah string.
 */
function capitalizeFirstLetter(string) {
    return string.charAt(0).toUpperCase() + string.slice(1);
}

/* 
 * Fungsi utilitas untuk menambahkan koma pada angka.
 */
function numberWithCommas(x) {
    return x.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
}

/* 
 * Fungsi untuk memuat semua komponen (header, main, footer) secara asinkron.
 * Setelah komponen dimuat, inisialisasi smooth scroll, filter kategori, dan slideshow banner.
 */
async function loadAllComponents() {
    await Promise.all([
        loadComponent('components/header.html', 'header'),
        loadComponent('components/main.html', 'main'),
        loadComponent('components/footer.html', 'footer')
    ]);

    // Tambahkan event listener setelah komponen dimuat
    addSmoothScroll();
    initCategoryFilters(); // Inisialisasi filter kategori
    initBannerSlideshow(); // Inisialisasi slideshow banner
    loadCategories();       // Muat kategori dari API
    loadProducts();         // Muat produk dari API
}

/* 
 * Fungsi untuk menambahkan smooth scroll pada tautan internal.
 * Menghaluskan pergerakan scroll ketika pengguna mengklik tautan dengan href yang dimulai dengan '#'.
 */
function addSmoothScroll() {
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                window.scrollTo({
                    top: target.offsetTop,
                    behavior: 'smooth'
                });
            }
        });
    });
}

/* 
 * Fungsi untuk menginisialisasi Slideshow Banner.
 * Mengatur pergantian slide otomatis setiap 5 detik dan interaksi dengan indikator.
 */
async function initBannerSlideshow() {
    const bannerSlider = document.getElementById('banner-slider');
    const bannerIndicators = document.getElementById('banner-indicators');

    try {
        const response = await fetch('http://localhost:8000/api/banners/read.php'); // Ganti dengan domain backend Anda
        if (!response.ok) throw new Error('Gagal mengambil banner');
        const banners = await response.json();

        banners.forEach((banner, index) => {
            const slide = document.createElement('div');
            slide.classList.add('slide');
            if (index === 0) slide.classList.add('active');
            // Gunakan image_url
            slide.innerHTML = `<img src="${banner.image_url}" alt="${banner.alt_text}">`;
            bannerSlider.appendChild(slide);

            const indicator = document.createElement('span');
            indicator.classList.add('indicator');
            if (index === 0) indicator.classList.add('active');
            indicator.setAttribute('data-slide', index);
            bannerIndicators.appendChild(indicator);
        });

        // Inisialisasi slideshow
        let currentIndex = 0;
        const slides = document.querySelectorAll('.banner-slider .slide');
        const indicators = document.querySelectorAll('.banner-indicators .indicator');
        const slideInterval = 5000; // 5 detik
        let intervalId = setInterval(nextSlide, slideInterval);

        function nextSlide() {
            slides[currentIndex].classList.remove('active');
            indicators[currentIndex].classList.remove('active');
            currentIndex = (currentIndex + 1) % slides.length;
            slides[currentIndex].classList.add('active');
            indicators[currentIndex].classList.add('active');
        }

        // Event listener untuk indikator
        indicators.forEach(indicator => {
            indicator.addEventListener('click', () => {
                clearInterval(intervalId);
                const index = parseInt(indicator.getAttribute('data-slide'));
                slides[currentIndex].classList.remove('active');
                indicators[currentIndex].classList.remove('active');
                slides[index].classList.add('active');
                indicators[index].classList.add('active');
                currentIndex = index;
                intervalId = setInterval(nextSlide, slideInterval);
            });
        });

    } catch (error) {
        console.error(error);
    }
}

/* 
 * Fungsi untuk mengambil dan menampilkan kategori.
 * Membuat tombol kategori berdasarkan data yang diterima dari API.
 */
async function loadCategories() {
    const categoriesContainer = document.getElementById('categories-container');

    try {
        const response = await fetch('http://localhost:8000/api/categories/read.php'); // Ganti dengan domain backend Anda
        if (!response.ok) throw new Error('Gagal mengambil kategori');
        const categories = await response.json();


        categories.forEach(category => {
            const button = document.createElement('button');
            button.classList.add('category-btn');
            button.setAttribute('data-category', category.name.toLowerCase());
            button.innerHTML = `
                <h3>${capitalizeFirstLetter(category.name)}</h3>
                <p>${category.description}</p>
            `;
            categoriesContainer.appendChild(button);
        });

        // Inisialisasi event listener untuk kategori
        initCategoryFilters();

    } catch (error) {
        console.error(error);
    }
}

/* 
 * Fungsi untuk mengambil dan menampilkan produk.
 * Menampilkan produk berdasarkan kategori yang dipilih.
 */
async function loadProducts(category = '') {
    const productsContainer = document.getElementById('products-container');
    const productsTitle = document.getElementById('products-title');
    productsContainer.innerHTML = '';
    productsTitle.textContent = 'Popular Products';

    let apiUrl = 'http://localhost:8000/api/products/read.php'; // Ganti dengan domain backend Anda

    try {
        const response = await fetch(apiUrl);
        if (!response.ok) throw new Error('Gagal mengambil produk');
        const products = await response.json();

        let filteredProducts = products;

        if (category) {
            filteredProducts = products.filter(product => {
                return product.category_name.toLowerCase() === category || category === 'others';
            });
            if (category === 'others') {
                productsTitle.textContent = 'All Products';
            } else {
                productsTitle.textContent = `${capitalizeFirstLetter(category)} Products`;
            }
        }

        if (!category) {
            productsTitle.textContent = 'Popular Products';
        }

        filteredProducts.forEach(product => {
            const productDiv = document.createElement('div');
            productDiv.classList.add('product');
            productDiv.setAttribute('data-category', product.category_name.toLowerCase());
            // Gunakan image_url
            productDiv.innerHTML = `
                <div class="image-container">
                    <img src="${product.image_url}" alt="${product.name}">
                </div>
                <div class="text-container">
                    <p class="product-title">${product.name}</p>
                    <p class="product-price">Rp. ${numberWithCommas(product.price)}</p>
                </div>
            `;
            productsContainer.appendChild(productDiv);
        });

    } catch (error) {
        console.error(error);
    }
}

/* 
 * Fungsi untuk menyaring produk berdasarkan kategori yang dipilih.
 * Menampilkan atau menyembunyikan produk sesuai dengan kategori.
 */
function filterProducts(category) {
    const products = document.querySelectorAll('.products .product');
    const title = document.getElementById('products-title');

    if (category) {
        products.forEach(product => {
            if (category === 'others' || product.getAttribute('data-category') === category) {
                product.style.display = 'flex'; // Tampilkan produk
            } else {
                product.style.display = 'none'; // Sembunyikan produk
            }
        });

        // Update judul sesuai kategori
        if (category === 'others') {
            title.textContent = 'All Products';
        } else {
            title.textContent = `${capitalizeFirstLetter(category)} Products`;
        }
    } else {
        // Tampilkan semua produk
        products.forEach(product => {
            product.style.display = 'flex'; // Tampilkan produk
        });
        title.textContent = 'Popular Products';
    }
}

/* 
 * Fungsi untuk menangani klik pada tombol kategori.
 * Mengatur kategori yang dipilih dan menerapkan penyaringan produk.
 */
function handleCategoryClick(event) {
    const category = event.currentTarget.getAttribute('data-category');

    if (selectedCategory === category) {
        // Jika kategori yang sama diklik lagi, batalkan filter
        selectedCategory = null;
    } else {
        // Pilih kategori baru
        selectedCategory = category;
    }

    // Terapkan penyaringan
    filterProducts(selectedCategory);
    updateCategoryButtons(); // Pastikan tombol kategori diperbarui
}

/* 
 * Fungsi untuk memperbarui tampilan tombol kategori.
 * Menambahkan atau menghapus kelas 'active' berdasarkan kategori yang dipilih.
 */
function updateCategoryButtons() {
    const buttons = document.querySelectorAll('.category-btn');
    buttons.forEach(button => {
        if (button.getAttribute('data-category') === selectedCategory) {
            button.classList.add('active');
        } else {
            button.classList.remove('active');
        }
    });
}

/* 
 * Fungsi untuk menginisialisasi event listener pada tombol kategori.
 * Menambahkan event listener untuk setiap tombol kategori.
 */
function initCategoryFilters() {
    const categoryButtons = document.querySelectorAll('.category-btn');
    categoryButtons.forEach(button => {
        button.addEventListener('click', handleCategoryClick);
    });
}

/* 
 * Fungsi untuk menambahkan smooth scroll pada tautan internal.
 * Sudah diimplementasikan dalam addSmoothScroll().
 * Fungsi ini dibiarkan kosong jika diperlukan ekstensi di masa depan.
 */
function addEventListeners() {
    // Tidak diperlukan karena event listeners sudah diinisialisasi di fungsi terkait
}

/* 
 * Fungsi untuk memuat semua komponen setelah DOM siap.
 */
document.addEventListener("DOMContentLoaded", () => {
    loadAllComponents();
});
