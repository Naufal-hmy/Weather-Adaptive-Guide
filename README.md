# 🌦️ Weather-Adaptive Travel Guide
**Proyek Tugas Akhir: Agentic Core dengan Laravel 13 & Vue 3**

Aplikasi ini adalah asisten perjalanan cerdas yang merekomendasikan destinasi wisata berdasarkan cuaca *real-time* menggunakan OpenWeather API. Dibangun dengan konsep **Agentic Logic** tanpa bergantung pada LLM/OpenAI.

## 🚀 Fitur Utama
1. **Interactive Map (Leaflet.js)**: Pengguna dapat mengklik titik mana pun di peta dunia untuk melihat cuaca.
2. **Agentic Logic**: *Service class* `WeatherGuideAgent` secara otomatis mengambil keputusan apakah harus menyarankan wisata Indoor (saat hujan) atau Outdoor (saat cerah).
3. **Admin Panel**: Menggunakan fitur role-based `is_admin` middleware untuk mengelola destinasi (CRUD).
4. **Supabase Database**: Menggunakan PostgreSQL Cloud database yang terhubung langsung melalui Eloquent ORM.

---

## 🧠 Ownership: Penjelasan Alur Request Life Cycle (Laravel 13)
*Gunakan teks ini untuk bahan presentasi ke dosen.*

Dalam aplikasi ini, setiap kali pengguna mengklik Peta atau melakukan pencarian, terjadi **Request Life Cycle** dengan alur sebagai berikut:

### 1. HTTP Request & Route
Pengguna melakukan *Request* (misal: mengklik peta yang mengirimkan koordinat lat/lon). *Request* ini pertama kali ditangkap oleh **`public/index.php`**, lalu diteruskan ke **Router** (`routes/web.php`).
```php
// web.php
Route::get('/dashboard', [GuideController::class, 'index']);
```

### 2. Middleware
Sebelum masuk ke Controller, *Request* melewati **Middleware**. Pada rute utama, kita menggunakan middleware `auth` dan `HandleInertiaRequests`.
- `auth`: Memastikan user sudah login.
- `HandleInertiaRequests`: Menyisipkan data global (seperti status `is_admin`) ke dalam setiap respon Vue.js.

### 3. Controller (Pengendali)
Setelah lolos Middleware, *Request* masuk ke `GuideController`. Controller ini bertugas menerima parameter (seperti `$lat` dan `$lon`), lalu memanggil **Agentic Core** (Service Logic) kita.
```php
$guideData = $this->agent->getRecommendations($city, $lat, $lon);
```

### 4. Service Logic / Agentic Core (`WeatherGuideAgent.php`)
Di sinilah letak **Logika Cerdas (Agentic)** berjalan.
- **Persepsi**: Agen menembak API OpenWeather berdasarkan koordinat yang diberikan untuk melihat "kondisi dunia nyata".
- **Kognisi**: Method `evaluateWeather()` mengevaluasi apakah data cuaca menunjukkan hujan/badai.
- **Aksi Otonom**: Jika hujan, Agen langsung mengeksekusi `Destination::indoor()->get()`. Jika cerah, ia mengeksekusi `Destination::outdoor()->get()`.

### 5. Response & View (Inertia Vue 3)
Setelah Agen mengambil keputusan dan mendapatkan rekomendasi dari *Supabase Database*, Controller mengemas data tersebut dan mengembalikannya ke *Frontend* menggunakan `Inertia::render()`.
```php
return Inertia::render('Dashboard', ['guideData' => $guideData]);
```
Di *Frontend*, `Dashboard.vue` secara *reactive* merender rekomendasi wisata di layar tanpa perlu *reload* halaman.

---

## 🛠️ Instalasi & Menjalankan Aplikasi

1. Clone repositori ini.
2. Jalankan `composer install` dan `npm install`.
3. Duplikat `.env.example` menjadi `.env`.
4. Isi `DB_CONNECTION=pgsql` dan sesuaikan kredensial **Supabase**.
5. Isi `OPENWEATHER_API_KEY` dengan API Key dari OpenWeather.
6. Jalankan migrasi: `php artisan migrate`.
7. Jalankan server backend: `php artisan serve`.
8. Jalankan kompilasi frontend: `npm run dev` atau `npm run build`.
