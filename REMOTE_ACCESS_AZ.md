# 🌐 NovusGate ilə Uzaqdan Giriş

**S-RCS-ə dünyanın istənilən yerindən təhlükəsiz qoşulun — statik IP olmadan!**

---

## 🎯 Problem

S-RCS-i Active Directory idarəetməsi üçün quraşdırmısınız, lakin:
- ❌ Serverinizin statik IP ünvanı yoxdur
- ❌ Port 8043-ü birbaşa internetə açmaq istəmirsiniz
- ❌ NAT/Firewall birbaşa bağlantını qeyri-mümkün edir
- ❌ Evdən, səfərdən və ya uzaq ofislərdən təhlükəsiz giriş lazımdır

---

## ✅ Həll: NovusGate VPN

**[NovusGate](https://github.com/Ali7Zeynalli/NovusGate)** — WireGuard® protokolu üzərində qurulmuş, özünüz host etdiyiniz VPN idarəetmə platformasıdır.

### Necə İşləyir

```
┌────────────────┐                              ┌──────────────────┐
│  Siz (Uzaqdan) │◄──── NovusGate VPN ────────►│   S-RCS Server   │
│   Ev/Səfər     │         Tunnel              │   (Ofisiniz)     │
│  10.10.10.3    │                              │   10.10.10.2     │
└────────────────┘                              └────────┬─────────┘
                                                         │
                                                    LAN Bağlantısı
                                                         │
                                                         ▼
                                               ┌──────────────────┐
                                               │ Active Directory │
                                               │ Domain Controller│
                                               └──────────────────┘
```

NovusGate-ə qoşulduqdan sonra:
- ✅ S-RCS-ə `https://10.10.10.2:8043` ünvanı ilə istənilən yerdən daxil olun
- ✅ Statik IP tələb olunmur
- ✅ Port yönləndirməsinə ehtiyac yoxdur
- ✅ İstənilən NAT və ya firewall arxasında işləyir

---

## 🚀 Quraşdırma

Ətraflı quraşdırma təlimatı üçün NovusGate sənədlərinə baxın:

👉 **[NovusGate Quraşdırma Təlimatı (AZ)](https://github.com/Ali7Zeynalli/NovusGate/blob/main/README_AZ.md#-sürətli-başlanğıc)**

👉 **[NovusGate Quraşdırma Təlimatı (EN)](https://github.com/Ali7Zeynalli/NovusGate/blob/main/README.md#-quick-start)**

---

## 🔒 Təhlükəsizlik Üstünlükləri

| Xüsusiyyət | Üstünlük |
|------------|----------|
| **Açıq Port Yoxdur** | S-RCS ictimai internetdən gizlidir |
| **WireGuard Şifrələməsi** | Müasir kriptoqrafiya (ChaCha20, Curve25519) |
| **Şəxsi Şəbəkə** | Yalnız VPN üzvləri daxil ola bilər |
| **Split Tunneling** | Yalnız S-RCS trafiki VPN-dən keçir |

---

## 📚 Ətraflı Məlumat

- **NovusGate Repo**: [github.com/Ali7Zeynalli/NovusGate](https://github.com/Ali7Zeynalli/NovusGate)
- **WireGuard Rəsmi**: [wireguard.com](https://www.wireguard.com/)

---

**Hazırlayan: [Əli Zeynallı](https://github.com/Ali7Zeynalli)**
