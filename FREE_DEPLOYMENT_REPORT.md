# FREE_DEPLOYMENT_REPORT.md

> Safo B2B — Free Deployment Plan
> Date: 2026-07-25

---

## Free Hosting Analysis (2026)

### Services Evaluated

| Service | Free Tier | MySQL | Redis | PHP 8.2 | Queue | Cron | Card Required | Verdict |
|---------|-----------|-------|-------|---------|-------|------|---------------|---------|
| **Oracle Cloud Always Free** | ✅ Forever | ✅ | ✅ | ✅ | ✅ | ✅ | Verification only ($0) | **Best option** |
| InfinityFree | ✅ Forever | ✅ | ❌ | ✅ 8.3 | ❌ | ❌ | No | Too limited |
| Render.com | ✅ Limited | ❌ PostgreSQL | ❌ | Via Docker | ❌ | ❌ | Yes | MySQL not free |
| Railway.app | ❌ $1/mo min | ✅ | ✅ | ✅ | ✅ | ✅ | Yes | Not free |
| Fly.io | ❌ No new free | ✅ | ✅ | ✅ | ✅ | ✅ | Yes | Closed free tier |
| Laravel Cloud | ❌ $5/mo min | ✅ | ✅ | ✅ | ✅ | ✅ | Yes | Not free |
| Koyeb | ✅ Limited | ❌ | ❌ | ✅ | ❌ | ❌ | Verification | Too limited |
| GitHub Pages | ✅ Forever | N/A | N/A | N/A | N/A | N/A | No | Static only |

### Chosen Architecture

```
┌─────────────────────────────────────────────────────┐
│           Oracle Cloud Always Free (ARM VM)         │
│           2 OCPUs • 12 GB RAM • 200 GB SSD         │
│                                                      │
│  ┌──────────────┐  ┌──────────┐  ┌──────────────┐  │
│  │  Nginx       │  │  MySQL 8 │  │  Redis 7     │  │
│  │  + PHP 8.2   │  │          │  │              │  │
│  │  + Laravel   │  │          │  │              │  │
│  └──────────────┘  └──────────┘  └──────────────┘  │
│                                                      │
│  ┌──────────────┐  ┌──────────┐                     │
│  │ Queue Worker │  │Scheduler │                     │
│  └──────────────┘  └──────────┘                     │
│                                                      │
│  Public IP: xxx.xxx.xxx.xxx                          │
│  API: http://xxx.xxx.xxx.xxx/api/v1/                │
└─────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────┐
│           GitHub Pages (Free Forever)               │
│                                                      │
│  Supplier Dashboard: Vue.js 3 SPA                   │
│  URL: https://htsoftpro-ui.github.io/safo-app/      │
└─────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────┐
│           Android App (Local Device)                │
│                                                      │
│  Points to: http://xxx.xxx.xxx.xxx/api/v1/          │
└─────────────────────────────────────────────────────┘
```

---

## Deployment Steps

### Step 1: Oracle Cloud Free Tier

1. Go to https://cloud.oracle.com
2. Sign up (credit card for verification only, $0 charges)
3. Create an ARM VM instance:
   - Image: Ubuntu 22.04 aarch64
   - Shape: VM.Standard.A1.Flex (2 OCPUs, 12 GB RAM)
   - Boot volume: 50 GB
4. Open ports 80 and 443 in security list
5. SSH into the instance
6. Run: `bash deploy-oracle-free.sh`

### Step 2: GitHub Pages (Supplier Dashboard)

1. Go to repository Settings → Pages
2. Source: GitHub Actions
3. Push to main triggers deployment
4. URL: `https://htsoftpro-ui.github.io/safo-app/`

### Step 3: Update API URLs

**Supplier Dashboard** (`safo-supplier-dashboard/src/api/index.ts`):
```typescript
// Change BASE_URL to your Oracle Cloud IP
const api = axios.create({
  baseURL: 'http://YOUR_ORACLE_IP/api/v1',
})
```

**Android App** (`safo-customer-android/app/src/main/java/com/safo/app/di/NetworkModule.kt`):
```kotlin
// Change BASE_URL to your Oracle Cloud IP
private const val BASE_URL = "http://YOUR_ORACLE_IP/api/v1/"
```

### Step 4: SSL (Optional)

```bash
# On Oracle Cloud VM:
sudo certbot --nginx -d your-domain.com
```

---

## Environment Variables

Set on Oracle Cloud VM in `/var/www/safo-app/safo-backend/.env`:

| Variable | Value |
|----------|-------|
| APP_ENV | production |
| APP_DEBUG | false |
| APP_URL | http://YOUR_IP |
| DB_CONNECTION | mysql |
| DB_HOST | 127.0.0.1 |
| DB_DATABASE | safo |
| DB_USERNAME | safo |
| DB_PASSWORD | (auto-generated) |
| REDIS_HOST | 127.0.0.1 |
| CACHE_STORE | redis |
| QUEUE_CONNECTION | redis |
| SESSION_DRIVER | redis |

---

## Free Tier Limits

### Oracle Cloud Always Free

| Resource | Limit |
|----------|-------|
| ARM OCPUs | 4 (using 2) |
| RAM | 24 GB (using 12) |
| Storage | 200 GB total |
| Outbound bandwidth | 10 TB/month |
| Duration | Forever (no expiry) |
| Credit card | Verification only ($0) |

### GitHub Pages

| Resource | Limit |
|----------|-------|
| Bandwidth | 100 GB/month |
| Storage | 1 GB |
| Build minutes | 2000/month |
| Custom domain | Supported |
| SSL | Free |
| Duration | Forever |

---

## What Works for Free

| Component | Status | Notes |
|-----------|--------|-------|
| Laravel API | ✅ | Full functionality |
| MySQL 8 | ✅ | Local on VM |
| Redis | ✅ | Local on VM |
| Queue Worker | ✅ | systemd service |
| Scheduler | ✅ | cron job |
| Vue Dashboard | ✅ | GitHub Pages |
| SSL | ✅ | Let's Encrypt |
| Custom Domain | ✅ | Point DNS to VM |
| Android Access | ✅ | Via public IP |

## What Doesn't Work for Free

| Component | Reason | Workaround |
|-----------|--------|------------|
| Push Notifications | Needs Firebase project | Add later |
| Email (SMTP) | Needs mail service | Use log driver |
| CDN | Not needed for MVP | Add Cloudflare later |
| Custom domain for API | Needs DNS setup | Use IP directly |

---

## Limitations

1. **Sleep**: Oracle VM is always on (no sleep). GitHub Pages is always on.
2. **Bandwidth**: 10 TB/month on Oracle (more than enough). 100 GB on GitHub Pages.
3. **Storage**: 200 GB total on Oracle (plenty for MVP).
4. **Support**: Community support only (no SLA).
5. **IP Changes**: Public IP may change on VM reboot (use dynamic DNS or elastic IP).

---

## Test Accounts

| Role | Phone | Password |
|------|-------|----------|
| Admin | 770000001 | password123 |
| Supplier 1 | 771000001 | password123 |
| Supplier 2 | 771000002 | password123 |
| Customer 1 | 772000001 | password123 |
| Customer 2 | 772000002 | password123 |

---

## Cost Summary

| Item | Cost |
|------|------|
| Oracle Cloud VM | $0/month (Always Free) |
| GitHub Pages | $0/month (free forever) |
| Domain (optional) | ~$10/year |
| SSL | $0 (Let's Encrypt) |
| **Total** | **$0/month** |

---

## Production Upgrade Path

When ready for production:

| Upgrade | Cost | Benefit |
|---------|------|---------|
| Custom domain | ~$10/year | Professional URL |
| Elastic IP | $0 (Oracle) | Stable IP |
| Managed MySQL | ~$15/month | Automated backups |
| Monitoring | Free (UptimeRobot) | Uptime alerts |
| CDN | Free (Cloudflare) | Global speed |
| Email | Free (Mailgun) | Transactional email |

---

## Status

- [x] Deployment script created
- [x] GitHub Actions workflow for dashboard
- [ ] Oracle Cloud account created (manual)
- [ ] VM deployed (manual)
- [ ] API tested from internet
- [ ] Dashboard tested from internet
- [ ] Android tested with public API

**Note**: Oracle Cloud account creation and VM deployment require manual steps (sign up, create instance, SSH). The `deploy-oracle-free.sh` script automates the server setup once connected.
