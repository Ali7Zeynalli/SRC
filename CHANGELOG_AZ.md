# Dəyişiklik Jurnalı

S-RCS üzərindəki bütün əhəmiyyətli dəyişikliklər bu faylda sənədləşdiriləcək.

---

## [1.3.0] - 2026-01-16

### 🔧 Təkmilləşdirmələr
- ⚙️ **Installer: Ətraf Mühit Əsaslı Konfiqurasiya**
  - Database parametrləri indi `.env` faylından Docker mühit dəyişənləri vasitəsilə avtomatik yüklənir
  - Installer-də database input sahələri artıq yalnız oxunur (read-only)
  - İstifadəçilərə quraşdırmadan əvvəl `.env` faylını düzəltmələri barədə xəbərdarlıq əlavə edildi
  - Credential idarəetməsinin `.env`-də mərkəzləşdirilməsi ilə təhlükəsizlik yaxşılaşdırıldı

---

## [1.3.0] - 2026-01-15

### ✨ Yeni Xüsusiyyətlər
- 🎫 **Tapşırıq İdarəetməsi (Helpdesk)** modulu əlavə edildi
  - Dəstək biletləri yaratma, redaktə etmə və silmə
  - Biletləri administratorlara təyin etmə
  - Status iş axını: Yeni → Təyin Edildi → Davam Edir → Həll Edildi → Bağlandı
  - İctimai şərhlər və daxili qeydlər
  - Kateqoriya idarəetməsi (Hardware, Software, Network və s.)
- 👤 **Təsirlənən İstifadəçi İnteqrasiyası** - Biletləri birbaşa AD istifadəçilərinə bağlama
  - Active Directory-dən istifadəçi axtarışı və seçimi
  - Ətraflı istifadəçi məlumatları (OU, Qruplar, Email)
  - Mövcud biletlərdə təsirlənən istifadəçini dəyişdirmə
- 📝 **Tam Audit Loglama** - Bütün bilet əməliyyatları Activity Logs-a yazılır
  - TICKET_CREATE - yeni bilet yaradıldıqda
  - TICKET_UPDATE - bilet detalları dəyişdikdə
  - TICKET_DELETE - bilet silindikdə
  - TICKET_ASSIGN - bilet təyin edildikdə
  - TICKET_STATUS - status dəyişdikdə
  - TICKET_COMMENT - şərh/qeyd əlavə edildikdə

### 🔧 Təkmilləşdirmələr
- İstifadəçi axtarışı display name və username ilə təkmilləşdirildi
- Bilet yaratma və redaktə modalları üçün UI yaxşılaşdırıldı
- Bütün SQL şemaları təmiz quraşdırma üçün tək `schema.sql` faylında birləşdirildi

### 📚 Sənədləşdirmə
- README.md-ə Task Management bölməsi əlavə edildi
- README_AZ.md-ə Tapşırıq İdarəetməsi bölməsi əlavə edildi
- Versiya izləmə üçün CHANGELOG.md yaradıldı
- Hər iki README-yə "Yeniliklər" bölməsi əlavə edildi
