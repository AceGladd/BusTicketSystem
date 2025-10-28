# Bilet Satın Alma Platformu

Modern web teknolojileri kullanılarak geliştirilmiş, veritabanı destekli otobüs bileti satış platformu.

## Teknolojiler

- **Backend:** PHP 8.2
- **Veritabanı:** SQLite
- **Frontend:** HTML, CSS, Bootstrap
- **Container:** Docker

## Kurulum

### Docker ile Çalıştırma

1. Projeyi klonlayın:
```bash
git clone https://github.com/[kullanici-adi]/bilet-satin-alma.git
cd bilet-satin-alma
```

2. Docker container'ı başlatın:
```bash
docker-compose up -d
```

3. Tarayıcınızda açın:
```
http://localhost:8080
```

## Varsayılan Kullanıcılar

Platform ilk çalıştırmada otomatik olarak demo kullanıcılar oluşturur:

### Admin
- Email: admin@example.com
- Şifre: admin123

### Firma Admin (Metro Turizm)
- Email: metro@example.com
- Şifre: firma123

### Firma Admin (Kamil Koç)
- Email: kamilkoc@example.com
- Şifre: firma123

### Normal Kullanıcı
- Email: user@example.com
- Şifre: user123
- Başlangıç Bakiyesi: 800 TL

## Özellikler

### Ziyaretçi
- Sefer arama ve listeleme
- Sefer detaylarını görüntüleme

### Kullanıcı (User)
- Hesap oluşturma ve giriş yapma
- Bilet satın alma (sanal kredi ile)
- Kupon kodu kullanma
- Bilet iptali (kalkıştan 1 saat öncesine kadar)
- Biletleri PDF olarak indirme
- Hesap bakiyesi görüntüleme

### Firma Admin
- Kendi firmasına ait seferleri yönetme (CRUD)
- Firma özel indirim kuponları oluşturma

### Admin
- Tüm firmaları yönetme
- Firma admin kullanıcıları oluşturma ve atama
- Genel indirim kuponları oluşturma
- Sistem genelinde yönetim

## Veritabanı Yapısı

Platform aşağıdaki tabloları içerir:
- **users:** Kullanıcı bilgileri
- **bus_company:** Otobüs firmaları
- **trips:** Sefer bilgileri
- **tickets:** Bilet kayıtları
- **booked_seats:** Rezerve koltuklar
- **coupons:** İndirim kuponları
- **user_coupons:** Kullanıcı kupon kullanımları

## Proje Yapısı

```
bilet-satin-alma/
├── config/
│   └── database.php          # Veritabanı bağlantısı
├── includes/
│   ├── auth.php              # Oturum yönetimi
│   ├── header.php            # Sayfa başlığı
│   └── footer.php            # Sayfa altbilgisi
├── database/
│   └── tickets.db            # SQLite veritabanı
├── admin/
│   ├── index.php             # Admin paneli
│   ├── companies.php         # Firma yönetimi
│   ├── firma_admins.php      # Firma admin yönetimi
│   └── coupons.php           # Kupon yönetimi
├── firma_admin/
│   ├── index.php             # Firma admin paneli
│   ├── trips.php             # Sefer yönetimi
│   └── coupons.php           # Firma kuponları
├── user/
│   ├── tickets.php           # Biletlerim
│   ├── purchase.php          # Bilet satın alma
│   ├── download_pdf.php      # PDF bilet indirme
│   └── profile.php           # Profil
├── api/
│   └── validate_coupon.php   # Kupon doğrulama API
├── assets/
│   ├── css/
│   │   └── style.css         # Özel CSS
│   └── js/
│       └── script.js         # JavaScript
├── index.php                 # Ana sayfa
├── login.php                 # Giriş
├── register.php              # Kayıt
├── logout.php                # Çıkış
├── Dockerfile                # Docker yapılandırması
└── docker-compose.yml        # Docker Compose yapılandırması
```

## Kullanım Senaryoları

### 1. Normal Kullanıcı Akışı
1. Ana sayfada kalkış/varış şehri ve tarihi seçerek sefer ara
2. Uygun seferi bul ve "Bilet Al" butonuna tıkla
3. Koltuk seç
4. İsteğe bağlı kupon kodu gir
5. Ödemeyi tamamla (sanal kredi ile)
6. "Biletlerim" sayfasından biletini görüntüle ve PDF indir

### 2. Firma Admin Akışı
1. Firma admin paneline giriş yap
2. "Sefer Yönetimi"nden yeni sefer ekle
3. "Kupon Yönetimi"nden firma özel kupon oluştur
4. İstatistikleri görüntüle

### 3. Admin Akışı
1. Admin paneline giriş yap
2. "Firma Yönetimi"nden yeni firma ekle
3. "Firma Admin Yönetimi"nden firma adminleri oluştur ve firmaya ata
4. "Kupon Yönetimi"nden genel kuponlar oluştur
5. Sistem istatistiklerini görüntüle
