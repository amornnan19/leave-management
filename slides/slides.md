---
theme: default
title: Leave Management — GhostShift
info: |
  Leave Management — a Laravel 13 + Filament v5 leave system by GhostShift.
class: text-center
highlighter: shiki
transition: slide-left
mdc: true
fonts:
  sans: Inter
  mono: JetBrains Mono
---

<div class="mono-label mb-4">GHOSTSHIFT // LEAVE MANAGEMENT</div>

# <span class="text-grad">Leave Management</span>

<p class="text-xl fog mt-2">ระบบลางาน end-to-end — Time off, handled.</p>

<div class="mt-10 text-sm fog">
  Laravel 13 · Filament v5 · SQLite · Deployed on Coolify
</div>

<div class="abs-br m-6 text-xs mono-label">
  leave-management.ghostshift.tech
</div>

---
layout: center
class: text-center
---

## ปัญหาที่แก้

<div class="grid grid-cols-3 gap-4 mt-8 text-left">
  <div class="card"><div class="teal text-2xl">📝</div><b>ขอลายุ่งยาก</b><div class="fog text-sm mt-1">ฟอร์มกระดาษ / chat / Excel กระจัดกระจาย</div></div>
  <div class="card"><div class="teal text-2xl">🧮</div><b>นับวันผิด</b><div class="fog text-sm mt-1">ลืมหักวันหยุด เสาร์อาทิตย์ ครึ่งวัน</div></div>
  <div class="card"><div class="teal text-2xl">🕵️</div><b>ไม่รู้ใครไม่อยู่</b><div class="fog text-sm mt-1">วางแผนงานยากเพราะไม่เห็นภาพรวม</div></div>
</div>

<p class="mt-10 text-lg">→ ระบบเดียวที่จัดการ <span class="teal">ขอลา · อนุมัติ · โควต้า · ปฏิทินทีม</span> ครบจบ</p>

---
layout: image-right
image: /portal-login.png
---

## สองหน้า หนึ่งระบบ

<div class="mono-label mb-3">DUAL FILAMENT PANELS</div>

<v-clicks>

- 🛠️ **Admin** `/admin` — HR & Manager <br/><span class="fog text-sm">จัดการพนักงาน, ประเภทลา, อนุมัติ, โควต้า</span>

- 👤 **Employee Portal** `/portal` — ทุกคน <br/><span class="fog text-sm">ยื่น/ติดตาม/ยกเลิกใบลาตัวเอง + ดูโควต้า</span>

- 🔒 **Role-based** — HR เห็นทั้งหมด · Manager เห็นเฉพาะทีม · Employee เห็นเฉพาะตัวเอง

</v-clicks>

<div class="mt-6 text-xs fog">admin = teal · portal = purple · แยกตัวตนชัด</div>

---

## Tech Stack

<div class="grid grid-cols-2 gap-6 mt-6">
<div>

### Backend
- **PHP 8.4** · **Laravel 13**
- **Filament v5** (admin UI) · Livewire v4
- **SQLite** — DB / cache / session / queue
- Pest 4 · Laravel Pint

</div>
<div>

### Ops & Brand
- **Docker** (serversideup/php) on **Coolify**
- SQLite persistent volume · auto-migrate
- GitHub push → **auto-deploy**
- GhostShift CI: dark + teal/purple gradient

</div>
</div>

<div class="mt-8 card text-center">
  <span class="teal text-2xl font-bold">168</span> <span class="fog">Pest tests เขียว</span>
  &nbsp;·&nbsp; ทุก feature ผ่าน <span class="teal">qaman</span> review &nbsp;·&nbsp; verify จริงผ่าน <span class="teal">Chrome DevTools MCP</span>
</div>

---

## Data Model

<div class="mono-label mb-4">6 MODELS · 3 ENUMS</div>

<div class="grid grid-cols-3 gap-3 text-sm">
  <div class="card"><b class="teal">User</b><div class="fog">พนักงาน — role, department, manager (self-ref)</div></div>
  <div class="card"><b class="teal">Department</b><div class="fog">แผนก + หัวหน้า</div></div>
  <div class="card"><b class="teal">LeaveType</b><div class="fog">ประเภทลา — โควต้า, สี, ครึ่งวัน, แนบไฟล์</div></div>
  <div class="card"><b class="teal">LeaveBalance</b><div class="fog">โควต้า/คน/ประเภท/ปี</div></div>
  <div class="card"><b class="teal">LeaveRequest</b><div class="fog">ใบลา (core)</div></div>
  <div class="card"><b class="teal">Holiday</b><div class="fog">วันหยุด (รองรับ recurring)</div></div>
</div>

<div class="mt-6 fog text-sm">
  <span class="purple">Enums:</span> UserRole · LeaveStatus (Pending/Approved/Rejected/Cancelled) · DayPeriod (Full/Morning/Afternoon)
</div>

---

## Business Rules — ตรวจตอนยื่น & แก้ไข

<div class="mono-label mb-4">LeaveRequestValidator</div>

<div class="grid grid-cols-2 gap-4">
<div class="card"><b class="teal">🚫 Overlap</b><div class="fog text-sm">กันลาทับวันกับใบที่ pending/approved (แก้ไขก็ยกเว้นตัวเอง)</div></div>
<div class="card"><b class="teal">💰 Balance</b><div class="fog text-sm">โควต้าต้องพอ (ลาไม่รับเงินยกเว้น)</div></div>
<div class="card"><b class="teal">📏 Max consecutive</b><div class="fog text-sm">จำกัดวันลาต่อเนื่องต่อประเภท</div></div>
<div class="card"><b class="teal">⏳ Min notice</b><div class="fog text-sm">ต้องยื่นล่วงหน้า X วัน (ตั้งต่อประเภท)</div></div>
</div>

<div class="mt-6 card">
  <b class="purple">LeaveDayCalculator</b> — นับวันทำงานจริง: ตัดเสาร์อาทิตย์ + วันหยุด + รองรับ <b class="teal">ครึ่งวัน</b> (เช้า/บ่าย = 0.5)
</div>

---
layout: image-right
image: /dashboard.png
---

## Calendar Dashboard

<div class="mono-label mb-3">เห็นภาพรวมทั้งทีม</div>

<v-clicks>

- 📅 ปฏิทินรายเดือน — **วันหยุด** + **ใครลาวันไหน**
- 🟢 **Company-wide** — ทุกคนเห็นเหมือนกัน รู้ว่าใครไม่อยู่
- 🌗 **ครึ่งวัน** แสดง `½AM` / `½PM`
- 🎨 สี dot ตาม **ประเภทลา** (มี legend)
- ◀ ▶ เลื่อนเดือน · วันนี้เน้น gradient

</v-clicks>

---
layout: two-cols
---

## Notifications

<div class="mono-label mb-3">REAL-TIME · IN-APP</div>

- ✅ **อนุมัติ/ปฏิเสธ** → แจ้งพนักงานเจ้าของใบลา
- 📨 **ยื่นใบลา** → แจ้ง manager (ไม่มีก็แจ้ง HR)
- 🔔 ขึ้นใน bell ของ panel ทันที

<div class="card mt-4 text-xs fog">
  ส่งแบบ <b class="teal">synchronous</b> — ไม่ต้องพึ่ง queue worker
  <br/>(เจอ & แก้ตอน build จริง)
</div>

::right::

## Security

<div class="mono-label mb-3 ml-4">HARDENED</div>

<div class="ml-4">

- 🔐 พนักงานเห็น **เฉพาะข้อมูลตัวเอง** (scoped query → foreign ID = 404)
- 🛡️ Forced ownership ตอน create (กัน tamper)
- 🚧 Policy + query scoping ทั้งสองชั้น
- ✅ Integrity validation (manager role, no self-manage)

</div>

---
layout: image-left
image: /login.png
---

## GhostShift Branding

<div class="mono-label mb-3">CI ทุกจุด</div>

<v-clicks>

- 🌑 Dark void + grain + scanline
- 🎨 Gradient wordmark (teal → purple)
- 🔠 Space Grotesk · JetBrains Mono
- 🖥️ Landing · Login · Panels · **403/404** · Calendar
- ✨ ไม่เหลือหน้า default Laravel เลย

</v-clicks>

<div class="mt-4 text-xs fog" v-click>
  ทำ build-free (inline CSS + Filament config) — robust บน Coolify
</div>

---

## Quality & Process

<div class="grid grid-cols-3 gap-4 mt-6 text-center">
  <div class="card"><div class="teal text-3xl font-bold">168</div><div class="fog text-sm">Pest tests เขียว</div></div>
  <div class="card"><div class="teal text-3xl font-bold">qaman</div><div class="fog text-sm">review ทุก feature</div></div>
  <div class="card"><div class="teal text-3xl font-bold">MCP</div><div class="fog text-sm">verify บน browser จริง</div></div>
</div>

<div class="mt-8">

**Workflow:** plan → `coderman` codes → `qaman` reviews → live-verify (MCP) → commit

</div>

<div class="mt-4 fog text-sm">
  จับบั๊กที่ test มองไม่เห็น: v5 namespace 500, queue-not-delivered, mixed-content https, Manager-sees-all-balances leak …
</div>

---

## Deployment — Coolify

<div class="mono-label mb-4">DOCKERFILE · SQLITE · AUTO-DEPLOY</div>

```bash
git push origin main      # → Coolify webhook → rebuild & deploy (~1–2 min)
```

<div class="grid grid-cols-2 gap-4 mt-4 text-sm">
  <div class="card"><b class="teal">Image</b><div class="fog">serversideup/php 8.4 · ไม่มี Node build</div></div>
  <div class="card"><b class="teal">Data</b><div class="fog">SQLite บน persistent volume · auto-migrate ตอน boot</div></div>
  <div class="card"><b class="teal">TLS + Domain</b><div class="fog">leave-management.ghostshift.tech (auto Let's Encrypt)</div></div>
  <div class="card"><b class="teal">Status</b><div class="teal">🟢 running · healthy</div></div>
</div>

---
layout: center
class: text-center
---

<div class="mono-label mb-4">LIVE NOW</div>

# <span class="text-grad">Time off, handled.</span>

<div class="mt-6 text-xl">
  <a href="https://leave-management.ghostshift.tech">leave-management.ghostshift.tech</a>
</div>

<div class="mt-10 fog text-sm">
  Laravel 13 · Filament v5 · 168 tests · deployed on Coolify
</div>

<div class="abs-br m-6 mono-label text-xs">GHOSTSHIFT</div>
