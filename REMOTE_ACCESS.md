# 🌐 Remote Access with NovusGate

**Access S-RCS from anywhere in the world — securely and without static IP!**

---

## 🎯 The Problem

You've deployed S-RCS to manage your Active Directory, but:
- ❌ Your server doesn't have a static public IP
- ❌ You don't want to expose port 8043 directly to the internet
- ❌ NAT/Firewall makes direct connections impossible
- ❌ You need secure access from home, travel, or remote offices

---

## ✅ The Solution: NovusGate VPN

**[NovusGate](https://github.com/Ali7Zeynalli/NovusGate)** is a self-hosted VPN control plane built on WireGuard® that creates a private network between all your devices.

### How It Works

```
┌────────────────┐                              ┌──────────────────┐
│  You (Remote)  │◄──── NovusGate VPN ────────►│   S-RCS Server   │
│  Home/Travel   │         Tunnel              │   (Your Office)  │
│  10.10.10.3    │                              │   10.10.10.2     │
└────────────────┘                              └────────┬─────────┘
                                                         │
                                                    LAN Connection
                                                         │
                                                         ▼
                                               ┌──────────────────┐
                                               │ Active Directory │
                                               │ Domain Controller│
                                               └──────────────────┘
```

Once connected to NovusGate:
- ✅ Access S-RCS at `https://10.10.10.2:8043` from anywhere
- ✅ No static IP required
- ✅ No port forwarding needed
- ✅ Works behind any NAT or firewall

---

## 🚀 Setup

For detailed installation instructions, see the NovusGate documentation:

👉 **[NovusGate Installation Guide (EN)](https://github.com/Ali7Zeynalli/NovusGate/blob/main/README.md#-quick-start)**

👉 **[NovusGate Installation Guide (AZ)](https://github.com/Ali7Zeynalli/NovusGate/blob/main/README_AZ.md#-sürətli-başlanğıc)**

---

## 🔒 Security Benefits

| Feature | Benefit |
|---------|---------|
| **No Exposed Ports** | S-RCS is hidden from the public internet |
| **WireGuard Encryption** | Modern cryptography (ChaCha20, Curve25519) |
| **Private Network** | Only VPN members can access |
| **Split Tunneling** | Only S-RCS traffic goes through VPN |

---

## 📚 Learn More

- **NovusGate Repository**: [github.com/Ali7Zeynalli/NovusGate](https://github.com/Ali7Zeynalli/NovusGate)
- **WireGuard Official**: [wireguard.com](https://www.wireguard.com/)

---

**Developed by [Ali Zeynalli](https://github.com/Ali7Zeynalli)**
