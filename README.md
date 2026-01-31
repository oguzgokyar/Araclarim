# Kişisel Araç ve Bilgi Kütüphanesi

Kişisel araçlarınızı, linklerinizi ve kaynaklarınızı yönetebileceğiniz, OpenAI destekli modern bir web arayüzüdür.

## Özellikler
- **Kategorize Edilmiş Liste:** Geliştirici araçlarını kategorilere ayırın.
- **Akıllı Ekleme (AI):** Sadece bir URL veya isim verin, yapay zeka (OpenAI) başlığı, açıklamayı, ikonu ve etiketleri sizin için bulsun.
- **Modern Arayüz:** Tailwind CSS ve Alpine.js ile oluşturulmuş, karanlık mod (dark mode) destekli şık tasarım.
- **Veritabanı Gerektirmez:** Tüm veriler `data/tools.json` içinde güvenli bir şekilde saklanır.
- **Güvenlik:** API anahtarlarınız sunucu tarafında saklanır, frontend'e gönderilmez.

## Kurulum (Sunucu)
1. Dosyaları PHP 7.0 veya üzeri destekleyen bir sunucuya yükleyin.
2. `data` klasörüne **yazma izni (CHMOD 755 veya 777)** verin.
3. Tarayıcıdan erişin.

## Yerel Çalıştırma (Bilgisayarınızda)
Eğer bilgisayarınızda PHP yüklü ise:
1. Bu klasörde bir terminal açın.
2. Şu komutu çalıştırın: `php -S localhost:8000`
3. Tarayıcınızda `http://localhost:8000` adresine gidin.

## Ayarlar
Uygulama açıldığında sağ menüden **Ayarlar** sekmesine gidin ve OpenAI API anahtarınızı girin. Bu anahtar `data/settings.json` dosyasında saklanacaktır.
